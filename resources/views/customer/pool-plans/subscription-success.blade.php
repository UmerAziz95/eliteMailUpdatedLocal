@extends('customer.layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-4 text-white">Trial Payment Successful! 🎉</h2>
                        <div style="background-color: #0bf04b2e; border: 1px solid #0bf04b;" class="mb-4 py-3 px-2 rounded-2">
                            <p class="mb-0 success ">Thank you for joining our Trial Plan! For 1 Week.</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 d-none">
                                <div class="p-3 rounded-2" style="background-color: #0bf04b2e; border: 1px solid #0bf04b;">
                                    <h6 class="success mb-2 text-uppercase fw-bold text-shadow">Subscription Details</h6>
                                    <p class="mb-1 success opacity-50"><strong>Subscription ID:</strong> {{ $poolOrder->chargebee_subscription_id }}</p>
                                    <p class="mb-1 success opacity-50"><strong>Pool Order ID:</strong> {{ $poolOrder->id }}</p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="p-3 rounded-2" style="background-color: #0bf04b2e; border: 1px solid #0bf04b;">
                                    <h6 class="success mb-2 text-uppercase fw-bold text-shadow">Pool Plan Information</h6>
                                    <p class="mb-1 success opacity-50"><strong>Pool:</strong> {{ $poolPlan->name ?? 'N/A' }}</p>
                                    <p class="mb-1 success opacity-50"><strong>Quantity:</strong> {{ $poolOrder->quantity ?? 1 }}</p>
                                    <p class="mb-1 success opacity-50"><strong>Amount Paid:</strong> ${{ $poolOrder->amount }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- <div class="mb-4 p-3 rounded-2" style="background-color: #17a2b82e; border: 1px solid #17a2b8;">
                            <h6 class="text-info mb-2 text-uppercase fw-bold">What's Next?</h6>
                            <p class="text-info opacity-75 mb-0">Your pool plan is now active. You can start placing orders and enjoy the benefits of our premium pool service.</p>
                        </div> -->

                        <div class="d-flex gap-3 justify-content-center">
                            <!-- <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-primary d-flex align-items-center">
                                <i class="ti ti-dashboard fs-5 me-2"></i> Go to Dashboard
                            </a> -->
                        
                            <!-- <a href="{{ route('customer.pool-orders.show', $poolOrder->id) }}" class="btn btn-outline-success d-flex align-items-center">
                                <i class="ti ti-eye fs-5 me-2"></i> View Pool Order Details
                            </a> -->
                            <a href="{{ route('customer.pool-orders.edit', $poolOrder->id) }}" class="btn btn-outline-warning d-flex align-items-center">
                                <i class="ti ti-pencil fs-5 me-2"></i> Select Trial Inboxes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection