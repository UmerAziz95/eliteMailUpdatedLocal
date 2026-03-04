@extends('customer.layouts.app')

@section('title', 'Pool Invoice Details')

@section('content')
<section class="py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0">Pool Invoice #{{ $invoice->id }}</h5>
                <small class="text-muted">Generated on {{ $invoice->created_at->format('F d, Y') }}</small>
            </div>
            <div>
                <button class="btn btn-primary" onclick="downloadInvoice('{{ $invoice->id }}', true)">
                    <i class="fa-solid fa-download me-2"></i> Download Invoice
                </button>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Invoice Status Banner -->
                <div class="col-12">
                    <div class="bg-label bg-label-{{ $invoice->status == 'paid' ? 'success' : 'warning' }} d-flex align-items-center mb-4 p-3 rounded-3">
                        <i class="fa-solid fa-{{ $invoice->status == 'paid' ? 'check-circle' : 'clock' }} me-2"></i>
                        <div>
                            <strong>{{ ucfirst($invoice->status) }}</strong>
                            @if($invoice->status == 'paid' && $invoice->paid_at)
                                - Paid on {{ \Carbon\Carbon::parse($invoice->paid_at)->format('F d, Y \a\t h:i A') }}
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="col-md-6 ">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-4">
                                <i class="fa-solid fa-file-invoice me-2"></i>Invoice Information
                            </h6>
                            <div class="mb-3">
                                <label class="text-warning small">Invoice Number</label>
                                <div class="fw-bold">{{ $invoice->id }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="text-warning small">Issue Date</label>
                                <div class="fw-bold">{{ $invoice->created_at->format('F d, Y') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="text-warning small">Order ID</label>
                                <div class="fw-bold">{{ $invoice->pool_order_id ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-4">
                                <i class="fa-solid fa-credit-card me-2"></i>Payment Information
                            </h6>
                            <div class="mb-3">
                                <label class="text-warning small">Amount</label>
                                <div class="fw-bold">{{ $invoice->currency ?? 'USD' }} ${{ number_format($invoice->amount, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="text-warning small">Payment Status</label>
                                <div>
                                    <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-warning small">Payment Date</label>
                                <div class="fw-bold">
                                    {{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('F d, Y h:i A') : 'Pending' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pool Order Details -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-4">
                                <i class="fa-solid fa-flask me-2"></i>Trial Order Information
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="text-warning small">Pool Order ID</label>
                                    <div class="fw-bold">{{ $invoice->pool_order_id ?? 'N/A' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-warning small">Order Status</label>
                                    <div class="fw-bold">
                                        @if($invoice->poolOrder)
                                            <span class="badge bg-primary">{{ ucfirst($invoice->poolOrder->status_manage_by_admin ?? 'N/A') }}</span>
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-warning small">Created At</label>
                                    <div class="fw-bold">{{ $invoice->created_at->format('F d, Y h:i A') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function downloadInvoice(invoiceId, isTrial = false) {
        if (isTrial) {
            window.location.href = `/customer/pool-invoices/${invoiceId}/download`;
        } else {
            window.location.href = `/customer/invoices/${invoiceId}/download`;
        }
    }
</script>
@endpush
@endsection
