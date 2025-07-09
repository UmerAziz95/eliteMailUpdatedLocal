<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->chargebee_invoice_id }}</title>
    <style>
        @page {   
            margin: 1cm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.5;
        }

        flex-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .company-info img {
            height: 40px;
            margin-bottom: 10px;
        }

        .company-info p {
            margin: 0;
            line-height: 1.4;
        }

        .invoice-summary {
            text-align: right;
            max-width: 45%;
        }

        .invoice-summary h2 {
            margin: 0 0 10px;
        }

        .invoice-summary p {
            margin: 2px 0;
        }

        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        .invoice-table th {
            background-color: #f5f5f5;
        }

        .payment-summary {
            margin-top: 20px;
        }

        .payment-summary table {
            width: 100%;
            font-size: 14px;
        }

        .payment-summary td {
            padding: 8px;
            text-align: right;
        }

        .footer {
            margin-top: 50px;
            width: 100%;
            text-align: center;
            color: #666;
            font-size: 0.9em;
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>


<body>
<table width="100%" style="margin-bottom: 30px; font-family: Arial, sans-serif;">
    <tr>
        <!-- LEFT SIDE: Logo + Address Sections -->
        <td width="50%" style="vertical-align: top; text-align: left; font-size: 14px; line-height: 1.6;">
            <!-- Logo -->
            <div style="margin-bottom: 15px;">
                <img src="{{ public_path('assets/logo/invoice-logo.jpg') }}" alt="Logo"
                     style="height: 30px; width: 200px; display: block;">
            </div>

 <!-- From -->
             <div style="margin-bottom: 15px;">
                <strong style="display: block; margin-bottom: 4px;">From</strong>
                PROJECT INBOX<br>
                Business Street<br>
                Suite 456<br>
                New York, NY 10001<br>
                support@projectinbox.ai<br>
                +1 (555) 123-4567
            </div>


            <!-- Billed To -->
            <div style="margin-bottom: 15px;">
                <strong style="display: block; margin-bottom: 4px;">Billed To</strong>
                {{ $invoice->user->name }}<br>
                {{ $invoice->user->email }}<br>
                @if($invoice->user->phone)
                    {{ $invoice->user->phone }}
                @endif
                 {{ $invoice->user->billing_address ?? 'N/A' }}<br> 
            </div>

            <!-- Billing Address -->
              {{-- <div style="margin-bottom: 15px;">
                <strong style="display: block; margin-bottom: 4px;">Billing Address</strong>
                {{ $invoice->user->billing_address ?? 'N/A' }}<br>
                {{ $invoice->user->email ?? 'N/A' }}
            </div> --}}
           
        </td>

        <!-- RIGHT SIDE: Invoice Summary + Subscription -->
        <td width="50%" style="vertical-align: top; text-align: right; font-size: 14px; line-height: 1.6;">
            <!-- Invoice Header -->
            <h2 style="margin: 0 0 10px; font-size: 24px;">INVOICE</h2>

            <!-- Invoice Info -->
            <p style="margin: 4px 0;"><strong>Invoice ID:</strong> {{ $invoice->chargebee_invoice_id }}</p>
            <p style="margin: 4px 0;"><strong>Customer ID:</strong> {{ $invoice->user->id }}</p>
            <p style="margin: 4px 0;"><strong>Customer Name:</strong> {{ $invoice->user->name }}</p>
            <p style="margin: 4px 0;"><strong>Invoice Amount:</strong> ${{ number_format($invoice->amount, 2) }} (USD)</p>
            <p style="margin: 4px 0;"><strong>Invoice Date:</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
            <p style="margin: 4px 0;">
                    <strong>Payment Status:</strong>
                    <span style="color: {{ $invoice->status === 'paid' ? '#28a745' : '#ffc107' }}; font-weight: bold;">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </p>

            <!-- Subscription Info -->
            <div style="margin-top: 30px;">
                <strong style="display: block; margin-bottom: 4px;">SUBSCRIPTION</strong>
                <strong>ID:</strong> {{ $invoice->order->chargebee_subscription_id ?? 'N/A' }}<br>
                <strong>Billing Period:</strong>
                {{ $invoice->order->created_at->format('M d, Y') }} -
                {{ $invoice->order->created_at->addMonth()->format('M d, Y') }}<br>
                <strong>Next Billing Date:</strong> {{ $invoice->order->created_at->addMonth()->format('M d, Y') }}
            </div>
        </td>
    </tr>
</table>
<!-- Subscription -->
   

    <table class="invoice-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Units</th>
                <th>Unit Price</th>
                <th>Amount (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Subscription Service<br><small style="color: #666;">Order ID: {{ $invoice->order->id }}</small></td>
                <td>1</td>
                <td>${{ number_format($invoice->amount, 2) }}</td>
                <td>${{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="payment-summary">
        <table>
            <tr>
                <td><strong>Total</strong></td>
                <td>${{ number_format($invoice->amount, decimals: 2) }}</td>
            </tr>
            @if(isset($invoice->metadata['tax']))
            <tr>
                <td>Tax</td>
                <td>${{ number_format($invoice->metadata['tax'], 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Payments</td>
                <td>(${{ number_format($invoice->amount + ($invoice->metadata['tax'] ?? 0), 2) }})</td>
            </tr>
            <tr style="font-weight: bold; font-size: 1.1em;">
                <td>Amount Due (USD)</td>
                <td>$0.00</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Thank you for your business!</p>
        @if($invoice->status !== 'paid')
        <p>Please make payment within 30 days of invoice date.</p>
        @endif
        <p style="font-size: 0.8em; color: #999;">Invoice ID: {{ $invoice->id }} | Generated on {{ now()->format('M d, Y
            H:i:s') }}</p>
    </div>
</body>

</html>