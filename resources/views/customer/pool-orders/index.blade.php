@extends('customer.layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-white mb-0">My Pool Orders</h2>
                <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-primary">
                    <i class="ti ti-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            @if($poolOrders->count() > 0)
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Pool Plan</th>
                                        <th>Qty</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($poolOrders as $order)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">#{{ $order->id }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $order->chargebee_subscription_id }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $order->poolPlan->name ?? 'N/A' }}</strong>
                                                @if($order->poolPlan && $order->poolPlan->capacity)
                                                    <br>
                                                    <small class="text-muted">Capacity: {{ $order->poolPlan->capacity }} orders</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $order->quantity ?? 1 }}</span>
                                            </td>
                                            <td>
                                                <strong>${{ number_format($order->amount, 2) }}</strong>
                                                <br>
                                                <small class="text-muted">{{ strtoupper($order->currency) }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $order->status === 'completed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                                <br>
                                                <small class="badge bg-secondary mt-1">
                                                    {{ ucfirst($order->status_manage_by_admin) }}
                                                </small>
                                            </td>
                                            <td>
                                                {{ $order->created_at->format('M d, Y') }}
                                                <br>
                                                <small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('customer.pool-orders.show', $order->id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-eye me-1"></i>View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-4 d-flex justify-content-center">
                    {{ $poolOrders->links() }}
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="ti ti-package-off text-muted" style="font-size: 3rem;"></i>
                        <h4 class="text-muted mt-3">No Pool Orders Found</h4>
                        <p class="text-muted mb-4">You haven't subscribed to any pool plans yet.</p>
                        <a href="{{ route('admin.pool-pricing.index') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-2"></i>Browse Pool Plans
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection