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
                        <h2 class="mb-4 text-white">Subscription Successful! 🎉</h2>
                        <div style="background-color: #0bf04b2e; border: 1px solid #0bf04b;" class="mb-4 py-3 px-2 rounded-2">
                            <p class="mb-0 success ">Thank you for subscribing! Your account has been successfully upgraded.</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="p-3 rounded-2" style="background-color: #0bf04b2e; border: 1px solid #0bf04b;">
                                    <h6 class="success mb-2 text-uppercase fw-bold text-shadow">Subscription Details</h6>
                                    <p class="mb-1 success opacity-50"><strong>Subscription ID:</strong> {{ $subscription_id }}</p>
                                    <p class="mb-1 success opacity-50"><strong>Order ID:</strong> {{ $order_id }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-2" style="background-color: #0bf04b2e; border: 1px solid #0bf04b;">
                                    <h6 class="success mb-2 text-uppercase fw-bold text-shadow">Plan Information</h6>
                                    <p class="mb-1 success opacity-50"><strong>Plan:</strong> {{ $plan->name ?? 'N/A' }}</p>
                                    <p class="mb-1 success opacity-50"><strong>Amount Paid:</strong> ${{ $amount }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 justify-content-center">
                            <a href="{{ route('customer.order.edit', $order_id) }}" class="btn btn-outline-primary d-flex align-items-center ">
                                <i class="ti ti-box fs-5 me-2"></i> Proceed With Your Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
