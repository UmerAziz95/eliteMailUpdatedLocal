@extends('customer.layouts.app')

@section('title', 'Orders')

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('customer.orders') }}" class="d-flex align-items-center justify-content-center" 
            style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <a href="{{ route('customer.orders.reorder', ['order_id' => $order->id]) }}" class="c-btn text-decoration-none">
            <i class="fa-solid fa-cart-plus"></i>
            Re-order
        </a>
    </div>

    @php
        $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
        $defaultProfilePic = 'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <h5 class="mb-3">Order #{{ $order->chargebee_invoice_id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span> {{ $order->created_at ? $order->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div class="border border-{{ $order->status == 'paid' ? 'success' : 'warning' }} rounded-2 py-1 px-2 text-{{ $order->status == 'paid' ? 'success' : 'warning' }} bg-transparent">
            {{ ucfirst($order->status ?? 'Pending') }}
        </div>
    </div>

    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link px-5 active" id="configuration-tab" data-bs-toggle="tab"
                data-bs-target="#configuration-tab-pane" type="button" role="tab"
                aria-controls="configuration-tab-pane" aria-selected="true">Configuration</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-5" id="email-tab" data-bs-toggle="tab"
                data-bs-target="#email-tab-pane" type="button" role="tab"
                aria-controls="email-tab-pane" aria-selected="false">Emails</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-5" id="subscription-tab" data-bs-toggle="tab"
                data-bs-target="#subscription-tab-pane" type="button" role="tab"
                aria-controls="subscription-tab-pane" aria-selected="false">Subscription</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-5" id="tickets-tab" data-bs-toggle="tab"
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

                        @if(isset($order->meta['email_config']))
                        <div class="d-flex align-items-center justify-content-between">
                            <span>Total Inboxes <br> {{ $order->meta['email_config']['total_inboxes'] ?? '0' }}</span>
                            <span>Inboxes per domain <br> {{ $order->meta['email_config']['inboxes_per_domain'] ?? '0' }}</span>
                        </div>
                        <hr>
                        <div class="d-flex flex-column">
                            <span class="opacity-50">Prefix Varients</span>
                            <span>{{ $order->meta['email_config']['prefix_variants'] ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Profile Picture URL</span>
                            <span>{{ $order->meta['email_config']['profile_picture_url'] ?? 'N/A' }}</span>
                        </div>
                        @else
                        <div class="text-muted">No email configuration data available</div>
                        @endif
                    </div>

                    <div class="card p-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-cart-plus"></i>
                            </div>
                            Products: <span class="text-success">${{ number_format($order->amount ?? 0, 2) }}</span>
                            <span>/Monthly</span>
                        </h6>

                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <img src="{{ $defaultImage }}" width="30" alt="Product Icon">
                            </div>
                            <div>
                                <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                <br>
                                @if(isset($order->meta['product_details']))
                                <span>{{ $order->meta['product_details']['quantity'] ?? '0' }} x ${{ number_format($order->meta['product_details']['unit_price'] ?? 0, 2) }} <small>/monthly</small></span>
                                @else
                                <span>Price details not available</span>
                                @endif
                            </div>
                        </div>
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

                        @if(optional($order->reorderInfo)->count() > 0)
                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50">Hosting Platform</span>
                                <span>{{ $order->reorderInfo->first()->hosting_platform }}</span>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50">Platform Login</span>
                                <span>{{ $order->reorderInfo->first()->platform_login }}</span>
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
                                <span class="opacity-50">Sequencer Login</span>
                                <span>{{ $order->reorderInfo->first()->sequencer_login }}</span>
                            </div>

                            <div class="d-flex flex-column">
                                <span class="opacity-50">Domains</span>
                                @php
                                    $domains = explode(',', $order->reorderInfo->first()->domains);
                                @endphp
                                @foreach($domains as $domain)
                                    <span>{{ trim($domain) }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted">No configuration information available</div>
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

                    <div class="table-responsive">
                        <table id="myTable" class="display w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($order->meta['emails']))
                                    @foreach($order->meta['emails'] as $email)
                                    <tr>
                                        <td>
                                            <img src="{{ $email['profile_picture'] ?? $defaultProfilePic }}"
                                                style="border-radius: 50%" height="35" width="35"
                                                class="object-fit-cover" alt="">
                                            {{ $email['name'] ?? 'N/A' }}
                                        </td>
                                        <td><i class="ti ti-mail text-success"></i> {{ $email['address'] ?? 'N/A' }}</td>
                                        <td>{{ $email['password'] ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
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
                    <button class="py-1 px-2 text-{{ $order->subscription->status == 'active' ? 'success' : 'warning' }} rounded-2 border border-{{ $order->subscription->status == 'active' ? 'success' : 'warning' }} bg-transparent">
                        {{ ucfirst($order->subscription->status) }}
                    </button>
                </div>

                <span>Next Billing</span>

                <div>
                    <span class="theme-text">Price</span>
                    <h6>${{ number_format($order->amount, 2) }} <span class="opacity-50">/monthly</span></h6>
                </div>

                <div>
                    <span class="theme-text">Date</span>
                    <h6>{{ $nextBillingInfo['next_billing_at'] ?? 'N/A'}}</h6>
                </div>

                @if($order->subscription->status == 'active')
                <div class="d-flex justify-content-end">
                    <button data-bs-toggle="modal" data-bs-target="#cancel_subscription"
                        class="py-1 px-2 text-danger rounded-2 border border-danger bg-transparent">Cancel
                        Subscription</button>
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
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Paid At</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            @push('scripts')
            <script>
            $(document).ready(function() {
                $('#invoicesTable').DataTable({
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
                            return d;
                        },
                        error: function(xhr, error, thrown) {
                            console.error('Error:', error);
                            toastr.error('Error loading invoice data');
                        }
                    },
                    columns: [
                        { data: 'chargebee_invoice_id', name: 'chargebee_invoice_id' },
                        { data: 'created_at', name: 'created_at' },
                        { data: 'created_at', name: 'created_at' },
                        { data: 'amount', name: 'amount' },
                        { data: 'paid_at', name: 'paid_at' },
                        { data: 'status', name: 'status' },
                        { data: 'action', name: 'action', orderable: false, searchable: false }
                    ],
                    order: [[1, 'desc']],
                    drawCallback: function(settings) {
                        if (settings.json && settings.json.error) {
                            toastr.error(settings.json.message || 'Error loading data');
                        }
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                });
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
                                <th>Tags</th>
                                <th>Ticket #</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Order #</th>
                                <th>Subscription #</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($order->meta['tickets']))
                                @foreach($order->meta['tickets'] as $ticket)
                                <tr>
                                    <td>{{ $ticket['tags'] ?? 'N/A' }}</td>
                                    <td>{{ $ticket['number'] ?? 'N/A' }}</td>
                                    <td>{{ isset($ticket['created_at']) ? \Carbon\Carbon::parse($ticket['created_at'])->format('d M, Y') : 'N/A' }}</td>
                                    <td>{{ $ticket['status'] ?? 'N/A' }}</td>
                                    <td>{{ $ticket['type'] ?? 'N/A' }}</td>
                                    <td>{{ $ticket['order_id'] ?? 'N/A' }}</td>
                                    <td>{{ $ticket['subscription_id'] ?? 'N/A' }}</td>
                                    <td>{{ $ticket['notes'] ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
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

                    <form action="{{ route('customer.subscription.cancel') }}" method="POST">
                        @csrf
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