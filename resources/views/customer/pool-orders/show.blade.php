@extends('customer.layouts.app')

@section('title', 'Pool Order Details')

@push('styles')
<style>
    .w-fit {
        width: fit-content !important;
    }
    .subscribe-btn {
        background: linear-gradient(135deg, #7367ef 0%, #8f84ff 100%);
        color: white !important;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .subscribe-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(115, 103, 239, 0.3);
        color: white !important;
    }
</style>
@endpush

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('customer.pool-orders.index') }}" class="d-flex align-items-center justify-content-center"
            style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        @if($poolOrder->status === 'completed' && $poolOrder->status_manage_by_admin === 'completed')
        <button class="c-btn text-decoration-none subscribe-btn" onclick="window.location.href='{{ route('customer.order.create') }}'">
            <i class="fa-solid fa-cart-plus"></i>
            New Order
        </button>
        @endif
    </div>

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <h5 class="mb-3">Pool Order #{{ $poolOrder->id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span> {{ $poolOrder->created_at ? $poolOrder->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div class="d-flex gap-2">
            <div class="border border-{{ $poolOrder->status_color }} rounded-2 py-1 px-2 text-{{ $poolOrder->status_color }} bg-transparent">
                {{ $poolOrder->status_label }}
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
            <button class="nav-link fs-6 px-5" id="subscription-tab" data-bs-toggle="tab"
                data-bs-target="#subscription-tab-pane" type="button" role="tab"
                aria-controls="subscription-tab-pane" aria-selected="false">Subscription</button>
        </li>
        <li class="nav-item d-none" role="presentation">
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
                                <i class="fa-solid fa-box"></i>
                            </div>
                            Pool Order Details
                        </h6>

                        <div class="d-flex align-items-center justify-content-between">
                            <span>Pool Plan <br> {{ $poolOrder->poolPlan->name ?? 'N/A' }}</span>
                            <span>Quantity <br> {{ $poolOrder->quantity ?? 1 }}</span>
                        </div>
                        <hr>
                        <div class="d-flex flex-column">
                            <span class="opacity-50">Amount</span>
                            <span>${{ number_format($poolOrder->amount, 2) }} {{ strtoupper($poolOrder->currency) }}</span>
                        </div>
                        
                        <!-- <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Order Status</span>
                            <span class="badge bg-{{ $poolOrder->status_color }} w-fit">{{ $poolOrder->status_label }}</span>
                        </div> -->

                        <!-- <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Admin Status</span>
                            <span class="badge bg-{{ $poolOrder->admin_status_color }} w-fit">{{ $poolOrder->admin_status_label }}</span>
                        </div> -->

                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Order Date</span>
                            <span>{{ $poolOrder->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>

                    

                    @if($poolOrder->pool_id && $poolOrder->pool)
                    <!-- Pool Details Card -->
                    <div class="card p-3 mt-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            Pool Profile Information
                        </h6>

                        <div class="row">
                            @if($poolOrder->pool->first_name || $poolOrder->pool->last_name)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Full Name</span>
                                    <span>{{ trim(($poolOrder->pool->first_name ?? '') . ' ' . ($poolOrder->pool->last_name ?? '')) }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->email)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Email</span>
                                    <span>{{ $poolOrder->pool->email }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->phone)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Phone</span>
                                    <span>{{ $poolOrder->pool->phone }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->location)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Location</span>
                                    <span>{{ $poolOrder->pool->location }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->company)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Company</span>
                                    <span>{{ $poolOrder->pool->company }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->job_title)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Job Title</span>
                                    <span>{{ $poolOrder->pool->job_title }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->linkedin_url)
                            <div class="col-md-12">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">LinkedIn URL</span>
                                    <a href="{{ $poolOrder->pool->linkedin_url }}" target="_blank" class="text-primary">
                                        {{ $poolOrder->pool->linkedin_url }}
                                    </a>
                                </div>
                            </div>
                            @endif
                        </div>

                        <hr>
                        <h6 class="mb-3"><i class="fa-solid fa-cog me-2"></i>Platform Configuration</h6>
                        
                        <div class="row">
                            @if($poolOrder->pool->hosting_platform)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Hosting Platform</span>
                                    <span class="badge bg-success w-fit">{{ ucfirst($poolOrder->pool->hosting_platform) }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->sending_platform)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Sending Platform</span>
                                    <span class="badge bg-primary w-fit">{{ ucfirst($poolOrder->pool->sending_platform) }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->forwarding_url)
                            <div class="col-md-12">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Domain Forwarding Destination URL</span>
                                    <a href="{{ $poolOrder->pool->forwarding_url }}" target="_blank" class="text-primary text-break">
                                        {{ $poolOrder->pool->forwarding_url }}
                                    </a>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->platform_login)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Platform Login</span>
                                    <span>{{ $poolOrder->pool->platform_login }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->platform_password)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Platform Password</span>
                                    <span class="">{{ $poolOrder->pool->platform_password }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->sequencer_login)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Cold Email Platform - Login</span>
                                    <span>{{ $poolOrder->pool->sequencer_login }}</span>
                                </div>
                            </div>
                            @endif

                            @if($poolOrder->pool->sequencer_password)
                            <div class="col-md-6">
                                <div class="d-flex flex-column mb-3">
                                    <span class="opacity-50">Cold Email Platform - Password</span>
                                    <span class="">{{ $poolOrder->pool->sequencer_password }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($poolOrder->sending_platform_data)
                    <!-- Sending Platform Data Card -->
                    <div class="card p-3 mt-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-mail-bulk"></i>
                            </div>
                            Sending Platform Configuration
                        </h6>

                        @php
                            $sendingData = is_string($poolOrder->sending_platform_data) 
                                ? json_decode($poolOrder->sending_platform_data, true) 
                                : $poolOrder->sending_platform_data;
                        @endphp

                        @if($poolOrder->sending_platform)
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Platform</span>
                            <span class="badge bg-primary w-fit">{{ ucfirst($poolOrder->sending_platform) }}</span>
                        </div>
                        @endif

                        @if($sendingData && is_array($sendingData))
                        <div class="row">
                            @foreach($sendingData as $key => $value)
                                @if($value && !in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                <div class="col-md-6">
                                    <div class="d-flex flex-column mb-3">
                                        <span class="opacity-50">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <span>{{ $value }}</span>
                                    </div>
                                </div>
                                @elseif($value && in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                <div class="col-md-6">
                                    <div class="d-flex flex-column mb-3">
                                        <span class="opacity-50">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <span class="text-muted">••••••••</span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @else
                        <div class="alert alert-info small mb-0">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            No sending platform data available
                        </div>
                        @endif
                    </div>
                    @endif

                    @if($poolOrder->hosting_platform_data)
                    <!-- Hosting Platform Data Card -->
                    <div class="card p-3 mt-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-server"></i>
                            </div>
                            Hosting Platform Configuration
                        </h6>

                        @php
                            $hostingData = is_string($poolOrder->hosting_platform_data) 
                                ? json_decode($poolOrder->hosting_platform_data, true) 
                                : $poolOrder->hosting_platform_data;
                        @endphp

                        @if($poolOrder->hosting_platform)
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Platform</span>
                            <span class="badge bg-success w-fit">{{ ucfirst($poolOrder->hosting_platform) }}</span>
                        </div>
                        @endif

                        @if($hostingData && is_array($hostingData))
                        <div class="row">
                            @foreach($hostingData as $key => $value)
                                @if($value && !in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                <div class="col-md-6">
                                    <div class="d-flex flex-column mb-3">
                                        <span class="opacity-50">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <span>{{ $value }}</span>
                                    </div>
                                </div>
                                @elseif($value && in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                <div class="col-md-6">
                                    <div class="d-flex flex-column mb-3">
                                        <span class="opacity-50">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                        <span class="text-muted">••••••••</span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @else
                        <div class="alert alert-info small mb-0">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            No hosting platform data available
                        </div>
                        @endif
                    </div>
                    @endif
                    <!-- <div class="card p-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-cart-plus"></i>
                            </div>
                            Products
                        </h6>

                        <div class="price-display-section">
                            @if($poolOrder->poolPlan)
                                <div class="d-flex align-items-center gap-3">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png" width="30" alt="Product Icon">
                                    </div>
                                    <div>
                                        <span class="opacity-50">{{ $poolOrder->poolPlan->name }}</span>
                                        <br>
                                        <span>({{ $poolOrder->quantity }} x ${{ number_format($poolOrder->poolPlan->price ?? 0, 2) }})</span>
                                    </div>
                                </div>
                                <h6><span class="theme-text small">Total:</span> ${{ number_format($poolOrder->amount, 2) }}</h6>
                            @else
                                <h6><span class="theme-text small">Total:</span> <small>Plan information not available</small></h6>
                            @endif
                        </div>
                    </div> -->
                </div>

                <div class="col-md-6">
                    <div class="card p-3 overflow-y-auto" style="max-height: 45rem">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-earth-europe"></i>
                            </div>
                            Domain Configuration
                        </h6>

                        @if($poolOrder->hasDomains())
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Selected Domains</span>
                            <span>{{ $poolOrder->selected_domains_count }} domains</span>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Total Inboxes</span>
                            <span>{{ $poolOrder->total_inboxes }}</span>
                        </div>

                        <div class="d-flex flex-column">
                            <span class="opacity-50">Domain Details</span>
                            @foreach($poolOrder->ready_domains_prefix as $index => $domain)
                                <div class="mt-2 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <strong>{{ $domain['domain_name'] ?? 'Unknown Domain' }}</strong>
                                                <span class="badge bg-{{ $domain['status'] === 'available' ? 'success' : 'primary' }} small">
                                                    {{ ucfirst($domain['status'] ?? 'Unknown') }}
                                                </span>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="">Inboxes per domain: {{ $domain['per_inbox'] ?? 1 }}</small>
                                                </div>
                                                <!-- <div class="col-6">
                                                    <small class="">Available: {{ $domain['available_inboxes'] ?? 0 }}</small>
                                                </div> -->
                                            </div>

                                            @if(!empty($domain['formatted_prefixes']))
                                                <div class="mt-2">
                                                    <small class=" d-block">Available Email Prefixes:</small>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach(array_slice($domain['formatted_prefixes'], 0, 3) as $email)
                                                            <span class="badge bg-light text-dark small">{{ $email }}</span>
                                                        @endforeach
                                                        @if(count($domain['formatted_prefixes']) > 3)
                                                            <span class="small ">+{{ count($domain['formatted_prefixes']) - 3 }} more</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            @if(!empty($domain['pool_info']['first_name']) || !empty($domain['pool_info']['last_name']))
                                                <div class="mt-2">
                                                    <small class="">
                                                        Profile: {{ trim(($domain['pool_info']['first_name'] ?? '') . ' ' . ($domain['pool_info']['last_name'] ?? '')) }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-start">
                                            <i class="fa-solid fa-globe text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @else
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <strong>Domain Configuration Required</strong><br>
                            This pool order hasn't been configured with domains yet. 
                            <a href="{{ route('customer.pool-orders.edit', $poolOrder->id) }}" class="btn btn-sm btn-primary ms-2">
                                <i class="fa-solid fa-edit me-1"></i>Configure Domains
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="subscription-tab-pane" role="tabpanel" aria-labelledby="subscription-tab"
            tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-credit-card"></i>
                        </div>
                        Subscription Details
                    </h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50">Subscription ID</span>
                                <span>{{ $poolOrder->chargebee_subscription_id ?? 'N/A' }}</span>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50">Customer ID</span>
                                <span>{{ $poolOrder->chargebee_customer_id ?? 'N/A' }}</span>
                            </div>

                            <!-- <div class="d-flex flex-column mb-3">
                                <span class="opacity-50">Invoice ID</span>
                                <span>{{ $poolOrder->chargebee_invoice_id ?? 'N/A' }}</span>
                            </div> -->
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50">Paid At</span>
                                <span>{{ $poolOrder->paid_at ? \Carbon\Carbon::parse($poolOrder->paid_at)->format('M d, Y h:i A') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($poolOrder->poolInvoices && $poolOrder->poolInvoices->count() > 0)
                    <hr>
                    <h6 class="mb-3">Pool Invoices</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice ID</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Paid At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($poolOrder->poolInvoices as $poolInvoice)
                                    <tr>
                                        <td>{{ $poolInvoice->chargebee_invoice_id }}</td>
                                        <td>${{ number_format($poolInvoice->amount, 2) }} {{ strtoupper($poolInvoice->currency) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $poolInvoice->status === 'paid' ? 'success' : 'warning' }}">
                                                {{ ucfirst($poolInvoice->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $poolInvoice->paid_at ? $poolInvoice->paid_at->format('M d, Y h:i A') : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- @if($poolOrder->poolPlan && $poolOrder->poolPlan->features)
                    <hr>
                    <h6 class="mb-3">Plan Features</h6>
                    <ul class="list-unstyled">
                        @foreach(json_decode($poolOrder->poolPlan->features, true) ?? [] as $feature)
                            <li class="mb-1"><i class="fa-solid fa-check text-success me-2"></i>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    @endif -->
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tickets-tab-pane" role="tabpanel" aria-labelledby="tickets-tab"
            tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-ticket"></i>
                        </div>
                        Support Tickets
                    </h6>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="mb-0">Need help with this pool order?</p>
                        <a href="" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i>Create Ticket
                        </a>
                    </div>

                    <!-- You can add ticket listing here if needed -->
                    <div class="text-center py-4">
                        <i class="fa-solid fa-ticket" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="mt-2 opacity-50">No tickets found for this order</p>
                        <p class="small opacity-50">Create a support ticket if you need assistance with your pool order</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Metadata (for debugging/admin purposes) -->
    @if(auth()->user()->hasRole('admin') && $poolOrder->meta)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">Technical Details (Admin Only)</h6>
            </div>
            <div class="card-body">
                <pre class="small ">{{ json_encode(json_decode($poolOrder->meta), JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif
</section>
@endsection