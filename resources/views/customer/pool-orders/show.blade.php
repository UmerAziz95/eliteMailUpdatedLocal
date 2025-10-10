@extends('customer.layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-white mb-0">Pool Order Details</h2>
                <a href="{{ route('customer.pool-orders.index') }}" class="btn btn-outline-primary">
                    <i class="ti ti-arrow-left me-2"></i>Back to Orders
                </a>
            </div>

            <div class="row">
                <!-- Order Summary -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header text-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Order ID:</strong> #{{ $poolOrder->id }}</p>
                                    <p><strong>Pool Plan:</strong> {{ $poolOrder->poolPlan->name ?? 'N/A' }}</p>
                                    <p><strong>Quantity:</strong> {{ $poolOrder->quantity ?? 1 }}</p>
                                    <p><strong>Amount:</strong> ${{ number_format($poolOrder->amount, 2) }} {{ strtoupper($poolOrder->currency) }}</p>
                                    <p><strong>Order Status:</strong> 
                                        <span class="badge bg-{{ $poolOrder->status_color }}">
                                            {{ $poolOrder->status_label }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Subscription ID:</strong> {{ $poolOrder->chargebee_subscription_id }}</p>
                                    <p><strong>Customer ID:</strong> {{ $poolOrder->chargebee_customer_id }}</p>
                                    <p><strong>Invoice ID:</strong> {{ $poolOrder->chargebee_invoice_id }}</p>
                                    <p><strong>Admin Status:</strong> 
                                        <span class="badge bg-{{ $poolOrder->admin_status_color }}">
                                            {{ $poolOrder->admin_status_label }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Order Date:</strong> {{ $poolOrder->created_at->format('M d, Y h:i A') }}</p>
                                    <p><strong>Paid At:</strong> {{ $poolOrder->paid_at ? \Carbon\Carbon::parse($poolOrder->paid_at)->format('M d, Y h:i A') : 'N/A' }}</p>
                                </div>
                                @if($poolOrder->poolPlan)
                                <div class="col-sm-6">
                                    <p><strong>Pool Capacity:</strong> {{ $poolOrder->poolPlan->capacity ?? 'N/A' }} orders</p>
                                    <p><strong>Pool Type:</strong> {{ $poolOrder->poolPlan->type ?? 'Standard' }}</p>
                                </div>
                                @endif
                            </div>

                            <!-- Domain Configuration Section -->
                            @if($poolOrder->hasDomains())
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="mb-3">
                                        <i class="ti ti-world me-2"></i>Domain Configuration
                                        <span class="badge bg-primary ms-2">{{ $poolOrder->selected_domains_count }} domains</span>
                                        <span class="badge bg-success ms-1">{{ $poolOrder->total_inboxes }} total inboxes</span>
                                    </h6>
                                    <div class="row">
                                        @foreach($poolOrder->domains as $domain)
                                            <div class="col-md-6 col-lg-4 mb-2">
                                                <div class="p-3 border rounded">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <small class="text-muted">Domain #{{ $domain['domain_id'] }}</small>
                                                            <div class="fw-medium">Per Inbox: {{ $domain['per_inbox'] }}</div>
                                                        </div>
                                                        <i class="ti ti-world-www text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @else
                            <hr>
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>Domain Configuration Required</strong><br>
                                This pool order hasn't been configured with domains yet. 
                                <a href="{{ route('customer.pool-orders.edit', $poolOrder->id) }}" class="btn btn-sm btn-primary ms-2">
                                    <i class="ti ti-edit me-1"></i>Configure Domains
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Pool Plan Details -->
                <div class="col-md-4">
                    @if($poolOrder->poolPlan)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">Pool Plan Details</h6>
                        </div>
                        <div class="card-body">
                            <h6>{{ $poolOrder->poolPlan->name }}</h6>
                            @if($poolOrder->poolPlan->description)
                                <p class="text-muted small">{{ $poolOrder->poolPlan->description }}</p>
                            @endif
                            
                            <ul class="list-unstyled small">
                                <li><strong>Capacity:</strong> {{ $poolOrder->poolPlan->capacity ?? 'Unlimited' }} orders</li>
                                @if($poolOrder->poolPlan->features)
                                    <li><strong>Features:</strong></li>
                                    <ul class="small text-muted">
                                        @foreach(json_decode($poolOrder->poolPlan->features, true) ?? [] as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </ul>
                        </div>
                    </div>
                    @endif

                    <!-- Actions -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            @if($poolOrder->status === 'completed' && $poolOrder->status_manage_by_admin === 'completed')
                                <a href="{{ route('customer.order.create') }}" class="btn btn-success btn-sm mb-2 w-100">
                                    <i class="ti ti-plus me-1"></i>Create New Order
                                </a>
                            @endif
                            
                            <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-primary btn-sm w-100">
                                <i class="ti ti-dashboard me-1"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pool Invoices -->
            @if($poolOrder->poolInvoices && $poolOrder->poolInvoices->count() > 0)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Pool Invoices</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="">
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
                    </div>
                </div>
            @endif

            <!-- Order Metadata (for debugging/admin purposes) -->
            @if(auth()->user()->hasRole('admin') && $poolOrder->meta)
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0">Technical Details (Admin Only)</h6>
                    </div>
                    <div class="card-body">
                        <pre class="small text-muted">{{ json_encode(json_decode($poolOrder->meta), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection