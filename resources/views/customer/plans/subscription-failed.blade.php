@extends('customer.layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-4">Subscription Failed</h2>
                        
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-0 mb-4">
                            <p class="mb-0"><strong>Error Message:</strong> {{ $error }}</p>
                        </div>

                        <div class="text-start mb-4">
                            <h5 class="mb-3">What can you do?</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Check if your payment information is correct
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Ensure you have sufficient funds in your account
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Contact your bank if the payment was declined
                                </li>
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Try a different payment method
                                </li>
                            </ul>
                        </div>

                        <div class="d-flex gap-3 justify-content-center">
                            <a href="{{ url()->previous() }}" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </a>
                            <a href="{{ route('customer.support') }}" class="btn btn-outline-primary">
                                <i class="fas fa-headset me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection