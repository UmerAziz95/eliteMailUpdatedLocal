@extends('admin.layouts.app')

@section('title', 'Pool Order Details')

@push('styles')
<style>
    .w-fit {
        width: fit-content !important;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="{{ route('admin.pool-domains.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa fa-chevron-left me-1"></i> Back to Pool Orders
        </a>
        <div class="d-flex gap-2">
            @if($poolOrder->status !== 'cancelled')
            <button class="btn btn-sm btn-danger" onclick="cancelPoolOrder({{ $poolOrder->id }})">
                <i class="fa fa-ban me-1"></i> Cancel Order
            </button>
            @endif
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-2">Pool Order #{{ $poolOrder->id ?? 'N/A' }}</h2>
            <p class="mb-0 ">
                <i class="fa fa-calendar me-1"></i>
                Order Date: {{ $poolOrder->created_at ? $poolOrder->created_at->format('M d, Y h:i A') : 'N/A' }}
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @php
                $statusColors = [
                    'pending' => 'warning',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger'
                ];
                $statusColor = $statusColors[$poolOrder->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $statusColor }} px-3 py-2">
                {{ ucfirst(str_replace('_', ' ', $poolOrder->status)) }}
            </span>
        </div>
    </div>

    <ul class="nav nav-pills mb-4 border-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white active" 
                    id="configuration-tab" data-bs-toggle="tab" data-bs-target="#configuration-tab-pane" 
                    type="button" role="tab" aria-controls="configuration-tab-pane" aria-selected="true">
                <i class="fa fa-cog me-1"></i>Configuration
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white" 
                    id="subscription-tab" data-bs-toggle="tab" data-bs-target="#subscription-tab-pane" 
                    type="button" role="tab" aria-controls="subscription-tab-pane" aria-selected="false">
                <i class="fa fa-credit-card me-1"></i>Subscription
            </button>
        </li>
    </ul>

    <div class="tab-content" id="poolOrderTabContent">
        <!-- Configuration Tab -->
        <div class="tab-pane fade show active" id="configuration-tab-pane" role="tabpanel" aria-labelledby="configuration-tab">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Order Details Card -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-4">
                                <div class="d-flex align-items-center justify-content-center"
                                    style="height: 35px; width: 35px; border-radius: 50%; color: #7367ef; border: 1px solid #7367ef">
                                    <i class="fa fa-box"></i>
                                </div>
                                Pool Order Details
                            </h5>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <p class="mb-0  small">Pool Plan</p>
                                    <p class="mb-0 fw-bold">{{ $poolOrder->poolPlan->name ?? 'N/A' }}</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-0  small">Quantity</p>
                                    <p class="mb-0 fw-bold">{{ $poolOrder->quantity ?? 1 }}</p>
                                </div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <p class="mb-1  small">Amount</p>
                                <h5 class="mb-0 text-primary">
                                    ${{ number_format($poolOrder->amount, 2) }} {{ strtoupper($poolOrder->currency) }}
                                </h5>
                            </div>

                            <div class="mb-3">
                                <p class="mb-1  small">Customer</p>
                                <p class="mb-0">
                                    <i class="fa fa-user me-1"></i>
                                    {{ $poolOrder->user->name ?? 'N/A' }}
                                </p>
                                <p class="mb-0 small ">
                                    <i class="fa fa-envelope me-1"></i>
                                    {{ $poolOrder->user->email ?? 'N/A' }}
                                </p>
                            </div>

                            @if($poolOrder->assigned_to)
                            <div class="mb-3">
                                <p class="mb-1  small">Assigned To</p>
                                <p class="mb-0">
                                    <i class="fa fa-user-check me-1"></i>
                                    {{ $poolOrder->assignedTo->name ?? 'N/A' }}
                                </p>
                                <p class="mb-0 small ">
                                    Assigned: {{ $poolOrder->assigned_at ? $poolOrder->assigned_at->format('M d, Y h:i A') : 'N/A' }}
                                </p>
                            </div>
                            @endif

                            <div>
                                <p class="mb-1  small">Order Date</p>
                                <p class="mb-0">{{ $poolOrder->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($poolOrder->pool_id && $poolOrder->pool)
                    <!-- Pool Profile Information Card -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-4">
                                <div class="d-flex align-items-center justify-content-center"
                                    style="height: 35px; width: 35px; border-radius: 50%; color: #7367ef; border: 1px solid #7367ef">
                                    <i class="fa fa-user"></i>
                                </div>
                                Pool Profile Information
                            </h5>

                            <div class="row">
                                @if($poolOrder->pool->first_name || $poolOrder->pool->last_name)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Full Name</p>
                                    <p class="mb-0">{{ trim(($poolOrder->pool->first_name ?? '') . ' ' . ($poolOrder->pool->last_name ?? '')) }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->email)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Email</p>
                                    <p class="mb-0">{{ $poolOrder->pool->email }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->phone)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Phone</p>
                                    <p class="mb-0">{{ $poolOrder->pool->phone }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->location)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Location</p>
                                    <p class="mb-0">{{ $poolOrder->pool->location }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->company)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Company</p>
                                    <p class="mb-0">{{ $poolOrder->pool->company }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->job_title)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Job Title</p>
                                    <p class="mb-0">{{ $poolOrder->pool->job_title }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->linkedin_url)
                                <div class="col-md-12 mb-3">
                                    <p class="mb-1  small">LinkedIn URL</p>
                                    <a href="{{ $poolOrder->pool->linkedin_url }}" target="_blank" class="text-primary">
                                        {{ $poolOrder->pool->linkedin_url }}
                                    </a>
                                </div>
                                @endif
                            </div>

                            <hr>
                            <h6 class="mb-3"><i class="fa fa-cog me-2"></i>Platform Configuration</h6>
                            
                            <div class="row">
                                @if($poolOrder->pool->hosting_platform)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Hosting Platform</p>
                                    <span class="badge bg-success">{{ ucfirst($poolOrder->pool->hosting_platform) }}</span>
                                </div>
                                @endif

                                @if($poolOrder->pool->sending_platform)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Sending Platform</p>
                                    <span class="badge bg-primary">{{ ucfirst($poolOrder->pool->sending_platform) }}</span>
                                </div>
                                @endif

                                @if($poolOrder->pool->forwarding_url)
                                <div class="col-md-12 mb-3">
                                    <p class="mb-1  small">Domain Forwarding Destination URL</p>
                                    <a href="{{ $poolOrder->pool->forwarding_url }}" target="_blank" class="text-primary text-break">
                                        {{ $poolOrder->pool->forwarding_url }}
                                    </a>
                                </div>
                                @endif

                                @if($poolOrder->pool->platform_login)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Platform Login</p>
                                    <p class="mb-0">{{ $poolOrder->pool->platform_login }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->platform_password)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Platform Password</p>
                                    <p class="mb-0">{{ $poolOrder->pool->platform_password }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->sequencer_login)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Cold Email Platform - Login</p>
                                    <p class="mb-0">{{ $poolOrder->pool->sequencer_login }}</p>
                                </div>
                                @endif

                                @if($poolOrder->pool->sequencer_password)
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Cold Email Platform - Password</p>
                                    <p class="mb-0">{{ $poolOrder->pool->sequencer_password }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($poolOrder->sending_platform_data)
                    <!-- Sending Platform Data Card -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-4">
                                <div class="d-flex align-items-center justify-content-center"
                                    style="height: 35px; width: 35px; border-radius: 50%; color: #7367ef; border: 1px solid #7367ef">
                                    <i class="fa fa-mail-bulk"></i>
                                </div>
                                Sending Platform Configuration
                            </h5>

                            @php
                                $sendingData = is_string($poolOrder->sending_platform_data) 
                                    ? json_decode($poolOrder->sending_platform_data, true) 
                                    : $poolOrder->sending_platform_data;
                            @endphp

                            @if($poolOrder->sending_platform)
                            <div class="mb-3">
                                <p class="mb-1  small">Platform</p>
                                <span class="badge bg-primary">{{ ucfirst($poolOrder->sending_platform) }}</span>
                            </div>
                            @endif

                            @if($sendingData && is_array($sendingData))
                            <div class="row">
                                @foreach($sendingData as $key => $value)
                                    @if($value && !in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1  small">{{ ucwords(str_replace('_', ' ', $key)) }}</p>
                                        <p class="mb-0">{{ $value }}</p>
                                    </div>
                                    @elseif($value && in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1  small">{{ ucwords(str_replace('_', ' ', $key)) }}</p>
                                        <p class="mb-0 ">••••••••</p>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                            @else
                            <div class="alert alert-info small mb-0">
                                <i class="fa fa-info-circle me-2"></i>
                                No sending platform data available
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($poolOrder->hosting_platform_data)
                    <!-- Hosting Platform Data Card -->
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-4">
                                <div class="d-flex align-items-center justify-content-center"
                                    style="height: 35px; width: 35px; border-radius: 50%; color: #7367ef; border: 1px solid #7367ef">
                                    <i class="fa fa-server"></i>
                                </div>
                                Hosting Platform Configuration
                            </h5>

                            @php
                                $hostingData = is_string($poolOrder->hosting_platform_data) 
                                    ? json_decode($poolOrder->hosting_platform_data, true) 
                                    : $poolOrder->hosting_platform_data;
                            @endphp

                            @if($poolOrder->hosting_platform)
                            <div class="mb-3">
                                <p class="mb-1  small">Platform</p>
                                <span class="badge bg-success">{{ ucfirst($poolOrder->hosting_platform) }}</span>
                            </div>
                            @endif

                            @if($hostingData && is_array($hostingData))
                            <div class="row">
                                @foreach($hostingData as $key => $value)
                                    @if($value && !in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1  small">{{ ucwords(str_replace('_', ' ', $key)) }}</p>
                                        <p class="mb-0">{{ $value }}</p>
                                    </div>
                                    @elseif($value && in_array(strtolower($key), ['password', 'api_key', 'token', 'secret']))
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1  small">{{ ucwords(str_replace('_', ' ', $key)) }}</p>
                                        <p class="mb-0 ">••••••••</p>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                            @else
                            <div class="alert alert-info small mb-0">
                                <i class="fa fa-info-circle me-2"></i>
                                No hosting platform data available
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Domain Configuration -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0" style="max-height: 800px;">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-4">
                                <div class="d-flex align-items-center justify-content-center"
                                    style="height: 35px; width: 35px; border-radius: 50%; color: #7367ef; border: 1px solid #7367ef">
                                    <i class="fa fa-globe"></i>
                                </div>
                                Domain Configuration
                            </h5>

                            @if($poolOrder->hasDomains())
                            <div class="row mb-3">
                                <div class="col-6">
                                    <p class="mb-1  small">Selected Domains</p>
                                    <p class="mb-0 fw-bold">{{ $poolOrder->selected_domains_count }} domains</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1  small">Total Inboxes</p>
                                    <p class="mb-0 fw-bold">{{ $poolOrder->total_inboxes }}</p>
                                </div>
                            </div>

                            <hr>

                            <div class="overflow-auto" style="max-height: 650px;">
                                <p class="mb-2  small fw-bold">Domain Details</p>
                                @foreach($poolOrder->ready_domains_prefix as $index => $domain)
                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">
                                                    <i class="fa fa-globe text-primary me-1"></i>
                                                    {{ $domain['domain_name'] ?? 'Unknown Domain' }}
                                                </h6>
                                                
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <small class="">Inboxes per domain:</small>
                                                        <span class="badge bg-info ms-1">{{ $domain['per_inbox'] ?? 1 }}</span>
                                                    </div>
                                                </div>

                                                @if(!empty($domain['formatted_prefixes']))
                                                    <div class="mt-2">
                                                        <small class=" d-block mb-1">Email Prefixes:</small>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach(array_slice($domain['formatted_prefixes'], 0, 3) as $email)
                                                                <span class="badge bg-light text-dark small">{{ $email }}</span>
                                                            @endforeach
                                                            @if(count($domain['formatted_prefixes']) > 3)
                                                                <span class="badge bg-secondary small">+{{ count($domain['formatted_prefixes']) - 3 }} more</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif

                                                @if(!empty($domain['pool_info']['first_name']) || !empty($domain['pool_info']['last_name']))
                                                    <div class="mt-2">
                                                        <small class="">
                                                            <i class="fa fa-user me-1"></i>
                                                            Profile: {{ trim(($domain['pool_info']['first_name'] ?? '') . ' ' . ($domain['pool_info']['last_name'] ?? '')) }}
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @else
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                <strong>Domain Configuration Pending</strong><br>
                                This pool order hasn't been configured with domains yet.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Tab -->
        <div class="tab-pane fade" id="subscription-tab-pane" role="tabpanel" aria-labelledby="subscription-tab">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title d-flex align-items-center gap-2 mb-4">
                                <div class="d-flex align-items-center justify-content-center"
                                    style="height: 35px; width: 35px; border-radius: 50%; color: #7367ef; border: 1px solid #7367ef">
                                    <i class="fa fa-credit-card"></i>
                                </div>
                                Subscription Details
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Subscription ID</p>
                                    <p class="mb-0">{{ $poolOrder->chargebee_subscription_id ?? 'N/A' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Customer ID</p>
                                    <p class="mb-0">{{ $poolOrder->chargebee_customer_id ?? 'N/A' }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Paid At</p>
                                    <p class="mb-0">
                                        {{ $poolOrder->paid_at ? \Carbon\Carbon::parse($poolOrder->paid_at)->format('M d, Y h:i A') : 'N/A' }}
                                    </p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <p class="mb-1  small">Amount</p>
                                    <p class="mb-0 fw-bold text-primary">
                                        ${{ number_format($poolOrder->amount, 2) }} {{ strtoupper($poolOrder->currency) }}
                                    </p>
                                </div>

                                @if($poolOrder->cancelled_at)
                                <div class="col-md-12 mb-3">
                                    <p class="mb-1  small">Cancelled At</p>
                                    <p class="mb-0 text-danger">
                                        {{ $poolOrder->cancelled_at->format('M d, Y h:i A') }}
                                    </p>
                                </div>
                                @endif
                            </div>

                            @if($poolOrder->poolInvoices && $poolOrder->poolInvoices->count() > 0)
                            <hr>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">
                                    <i class="fa fa-file-invoice me-2"></i>Pool Invoices
                                </h6>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Invoice ID</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Paid At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($poolOrder->poolInvoices as $invoice)
                                        <tr>
                                            <td>{{ $invoice->chargebee_invoice_id ?? 'N/A' }}</td>
                                            <td>${{ number_format($invoice->amount ?? 0, 2) }}</td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'paid' => 'success',
                                                        'pending' => 'warning',
                                                        'failed' => 'danger',
                                                        'cancelled' => 'secondary'
                                                    ];
                                                    $statusColor = $statusColors[$invoice->status ?? 'pending'] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $statusColor }}">
                                                    {{ ucfirst($invoice->status ?? 'pending') }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('M d, Y h:i A') : 'N/A' }}
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.pool-orders.invoices.download', $invoice->id) }}">
                                                                <i class="fa-solid fa-download me-1"></i>Download PDF
                                                            </a>
                                                        </li>
                                                        @if($invoice->invoice_url)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ $invoice->invoice_url }}" target="_blank">
                                                                <i class="fa-solid fa-external-link-alt me-1"></i>View Invoice
                                                            </a>
                                                        </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <hr>
                            <div class="text-center py-4">
                                <i class="fa fa-file-invoice" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 ">No invoices found for this pool order</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function cancelPoolOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this pool order?')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.pool-orders.cancel") }}',
        type: 'POST',
        data: {
            order_id: orderId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            toastr.success(response.message || 'Pool order cancelled successfully');
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Error cancelling pool order';
            toastr.error(errorMsg);
        }
    });
}
</script>
@endpush
