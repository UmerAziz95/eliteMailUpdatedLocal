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

@php
dd($invoice);
exit();
@endphp

<body>
    <table width="100%" style="margin-bottom: 30px; vertical-align: top;">
        <tr>
            <!-- LEFT: Logo and company info -->
            <td width="50%" style="vertical-align: top;">
                <div style="display: block;">
                    <img src="{{ public_path('assets/logo/logo_white_back.png') }}" alt="Logo"
                        style="height: 80px; width: auto; display: block; margin-bottom: 10px;">
                    <p style="margin: 0; font-size: 14px; line-height: 1.4;">
                        <strong>Billing Address</strong>
                        <br>
                        {{ $$invoice->user->billing_address ?? "N/A" }}<br>
                        {{ $$invoice->user->email ?? "N/A" }}
                    </p>
                </div>
            </td>

            <!-- RIGHT: Invoice summary -->
            <td width="50%" style="vertical-align: top; text-align: right;">
                <h2 style="margin: 0 0 10px;">INVOICE</h2>
                <p style="margin: 4px 0;"><strong>Invoice #</strong> {{ $invoice->chargebee_invoice_id }}</p>
                <p style="margin: 4px 0;"><strong>Invoice Date</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                <p style="margin: 4px 0;"><strong>Invoice Amount</strong> ${{ number_format($invoice->amount, 2) }}
                    (USD)</p>
                <p style="margin: 4px 0;"><strong>Customer ID</strong> {{ $invoice->user->id }}</p>
                <p
                    style="color: {{ $invoice->status === 'paid' ? '#28a745' : '#ffc107' }}; font-weight: bold; margin: 4px 0;">
                    {{ strtoupper($invoice->status) }}
                </p>
            </td>
        </tr>
    </table>


    <div class="billing-section">
        <div>
            <strong>BILLED TO</strong><br>
            {{ $invoice->user->name }}<br>
            {{ $invoice->user->email }}<br>
            @if($invoice->user->phone)
            {{ $invoice->user->phone }}
            @endif
        </div>
        <div style="text-align: right;">
            <strong>SUBSCRIPTION</strong><br>
            ID {{ $invoice->order->subscription_id ?? 'N/A' }}<br>
            Billing Period {{ $invoice->order->created_at->format('M d, Y') }} - {{
            $invoice->order->created_at->addMonth()->format('M d, Y') }}<br>
            Next Billing Date {{ $invoice->order->created_at->addMonth()->format('M d, Y') }}
        </div>
    </div>

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