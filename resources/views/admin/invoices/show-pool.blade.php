@extends('admin.layouts.app')

@section('title', 'Pool Invoice Details')

@section('content')
<section class="py-3">
    <div class="container">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0">Pool Invoice #{{ $invoice->chargebee_invoice_id }}</h5>
                    <small class="text-muted">Generated on {{ $invoice->created_at->format('F d, Y') }}</small>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="downloadInvoice('{{ $invoice->chargebee_invoice_id }}')">
                        <i class="fa-solid fa-download me-2"></i> Download Invoice
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Invoice Status Banner -->
                    <div class="col-12">
                        <div class="alert alert-{{ $invoice->status == 'paid' ? 'primary' : 'warning' }} d-flex align-items-center mb-4">
                            <i class="fa-solid fa-{{ $invoice->status == 'paid' ? 'check-circle' : 'clock' }} me-2"></i>
                            <div>
                                <strong>{{ ucfirst($invoice->status) }}</strong>
                                @if($invoice->status == 'paid')
                                    - Paid on {{ \Carbon\Carbon::parse($invoice->paid_at)->format('F d, Y \a\t h:i A') }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="col-md-6 ">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-title mb-4 text-primary">
                                    <i class="fa-solid fa-file-invoice me-2"></i>Invoice Information
                                </h6>
                                <div class="mb-3">
                                    <label class="text-warning small">Invoice Number</label>
                                    <div class="fw-bold">{{ $invoice->chargebee_invoice_id }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-warning small">Issue Date</label>
                                    <div class="fw-bold">{{ $invoice->created_at->format('F d, Y') }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="text-warning small">Customer</label>
                                    <div class="fw-bold">{{ $invoice->user->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="col-md-6">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <h6 class="card-title mb-4 text-primary">
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
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title mb-4 text-primary">
                                    <i class="fa-solid fa-box me-2"></i>Pool Order Information
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="text-warning small">Pool Order ID</label>
                                        <div class="fw-bold">{{ $invoice->pool_order_id ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-warning small">Customer Email</label>
                                        <div class="fw-bold">{{ $invoice->user->email ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="text-warning small">ChargebBee Customer ID</label>
                                        <div class="fw-bold">{{ $invoice->chargebee_customer_id ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
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
    function downloadInvoice(invoiceId) {
        window.location.href = `/admin/pool-invoices/${invoiceId}/download`;
    }
</script>
@endpush
