@extends('customer.layouts.app')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="mb-4 text-white">Pool Plan Subscription Cancelled</h2>
                        <div style="background-color: #ffc1072e; border: 1px solid #ffc107;" class="mb-4 py-3 px-2 rounded-2">
                            <p class="mb-0 text-warning">Your pool plan subscription process was cancelled or interrupted.</p>
                        </div>
                        
                        <div class="mb-4 p-3 rounded-2" style="background-color: #6c757d2e; border: 1px solid #6c757d;">
                            <h6 class="text-muted mb-2 text-uppercase fw-bold">What Happened?</h6>
                            <p class="text-muted mb-2">The pool plan subscription was not completed. This could be due to:</p>
                            <ul class="text-muted text-start mb-0">
                                <li>Payment was cancelled or declined</li>
                                <li>Browser session was interrupted</li>
                                <li>You chose to return to the previous page</li>
                            </ul>
                        </div>

                        <div class="mb-4 p-3 rounded-2" style="background-color: #17a2b82e; border: 1px solid #17a2b8;">
                            <h6 class="text-info mb-2 text-uppercase fw-bold">Need Help?</h6>
                            <p class="text-info mb-0">If you experienced any issues during the subscription process, please contact our support team for assistance.</p>
                        </div>

                        <div class="d-flex gap-3 justify-content-center">
                            <a href="{{ route('admin.pool-pricing.index') }}" class="btn btn-outline-primary d-flex align-items-center">
                                <i class="ti ti-arrow-left fs-5 me-2"></i> Back to Pool Plans
                            </a>
                            <a href="{{ route('customer.dashboard') }}" class="btn btn-secondary d-flex align-items-center">
                                <i class="ti ti-dashboard fs-5 me-2"></i> Go to Dashboard
                            </a>
                            <a href="{{ route('admin.ticket.create') }}" class="btn btn-info d-flex align-items-center">
                                <i class="ti ti-headphones fs-5 me-2"></i> Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection