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
                        <h2 class="mb-4">Subscription Successful! 🎉</h2>
                        <div class="alert alert-success bg-success bg-opacity-10 border-0 mb-4">
                            <p class="mb-0">Thank you for subscribing! Your account has been successfully upgraded.</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-success">
                                    <h6 class="text-muted mb-2">Subscription Details</h6>
                                    <p class="mb-1"><strong>Subscription ID:</strong> {{ $subscription_id }}</p>
                                    <p class="mb-1"><strong>Order ID:</strong> {{ $order_id }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-success">
                                    <h6 class="text-muted mb-2">Plan Information</h6>
                                    <p class="mb-1"><strong>Plan:</strong> {{ $plan->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Amount Paid:</strong> ${{ $amount }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 justify-content-center">
                            <a href="{{ route('customer.pricing') }}" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-2"></i>Manage Subscription
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
