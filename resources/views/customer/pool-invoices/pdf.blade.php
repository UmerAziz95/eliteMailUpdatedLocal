<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pool Invoice #{{ $poolInvoice->chargebee_invoice_id }}</title>
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
        .invoice-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .invoice-header h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        .invoice-reference {
            margin: 15px 0;
            color: #666;
        }
        .invoice-info {
            margin-bottom: 40px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            width: 50%;
            vertical-align: top;
            padding: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .total-section {
            float: right;
            width: 350px;
            margin-top: 20px;
            margin-left: auto;
            clear: both;
        }
        .total-section table {
            width: 100%;
            margin-left: auto;
        }
        .total-section td:first-child {
            text-align: left;
            padding-right: 20px;
        }
        .total-section td:last-child {
            text-align: right;
            width: 120px;
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
        .footer p {
            margin: 5px 0;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #000;">
    <table width="100%" style="margin-bottom: 30px;">
        <tr>
            <!-- LEFT SIDE: Logo + From + Bill To -->
            <td width="50%" style="vertical-align: top; text-align: left; line-height: 1.6;">
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

                <!-- Bill To -->
                <div style="margin-bottom: 15px;">
                    <strong style="display: block; margin-bottom: 4px;">Billed To</strong>
                    {{ $poolInvoice->user->name }}<br>
                    {{ $poolInvoice->user->email }}<br>
                    @if($poolInvoice->user->phone)
                        {{ $poolInvoice->user->phone }}<br>
                    @endif
                    {{ $poolInvoice->user->billing_address ?? 'N/A' }}<br>
                </div>
            </td>

            <!-- RIGHT SIDE: Invoice Info + Status -->
            <td width="50%" style="vertical-align: top; text-align: right; line-height: 1.6;">
                <h2 style="margin: 0 0 10px; font-size: 24px;">POOL INVOICE</h2>

                <p style="margin: 4px 0;"><strong>Invoice #</strong> {{ $poolInvoice->chargebee_invoice_id }}</p>
                <p style="margin: 4px 0;"><strong>Pool Order #</strong> {{ $poolInvoice->poolOrder->id }}</p>
                <p style="margin: 4px 0;"><strong>Invoice Date:</strong> {{ $poolInvoice->created_at->format('M d, Y') }}</p>
                <p style="margin: 4px 0;">
                    <strong>Payment Status:</strong>
                    <span style="color: {{ $poolInvoice->status === 'paid' ? '#28a745' : '#ffc107' }}; font-weight: bold;">
                        {{ ucfirst($poolInvoice->status) }}
                    </span>
                </p>
                
                <!-- Subscription Info -->
                <div style="margin-top: 30px;">
                    <strong style="display: block; margin-bottom: 4px;">SUBSCRIPTION</strong>
                    <strong>ID:</strong> {{ $poolInvoice->poolOrder->chargebee_subscription_id ?? 'N/A' }}<br>
                    @if($poolInvoice->paid_at)
                    <strong>Paid Date:</strong> {{ $poolInvoice->paid_at->format('M d, Y') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table" width="100%" cellpadding="10" cellspacing="0" border="1"
           style="border-collapse: collapse; margin-bottom: 30px;">
        <thead style="background-color: #f5f5f5;">
            <tr>
                <th align="left">Description</th>
                <th>Plan</th>
                <th>Quantity</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Pool Subscription Service<br>
                    <small style="color: #666;">Pool Order ID: {{ $poolInvoice->poolOrder->id }}</small>
                </td>
                <td>{{ optional($poolInvoice->poolOrder->poolPlan)->name ?? 'N/A' }}</td>
                <td>{{ $poolInvoice->poolOrder->quantity ?? 1 }}</td>
                <td>${{ number_format($poolInvoice->amount, 2) }} {{ strtoupper($poolInvoice->currency) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="total-section" style="width: 100%; margin-bottom: 40px;">
        <table width="100%" style="font-size: 14px;">
            <tr>
                <td align="right" width="80%">Subtotal:</td>
                <td align="right">${{ number_format($poolInvoice->amount, 2) }}</td>
            </tr>
            @if(isset($poolInvoice->meta['tax']))
            <tr>
                <td align="right">Tax:</td>
                <td align="right">${{ number_format($poolInvoice->meta['tax'], 2) }}</td>
            </tr>
            @endif
            <tr style="font-size: 16px; font-weight: bold; border-top: 2px solid #333;">
                <td align="right">Total:</td>
                <td align="right">${{ number_format($poolInvoice->amount, 2) }} {{ strtoupper($poolInvoice->currency) }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Information (if paid) -->
    @if($poolInvoice->status === 'paid' && $poolInvoice->paid_at)
    <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 30px;">
        <strong>Payment Information</strong><br>
        Payment Method: Credit Card<br>
        Transaction Date: {{ $poolInvoice->paid_at->format('M d, Y h:i A') }}<br>
        Transaction ID: {{ $poolInvoice->chargebee_invoice_id }}
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>If you have any questions about this invoice, please contact us at support@projectinbox.ai</p>
         <p style="font-size: 0.8em; color: #999;">
            Invoice ID: {{ $poolInvoice->chargebee_invoice_id }} | Generated on {{ now()->format('M d, Y H:i:s') }}
        </p>
    </div>
</body>
</html>
