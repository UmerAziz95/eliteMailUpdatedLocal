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
                {{ $invoice->user->name }}<br>
                {{ $invoice->user->email }}<br>
                @if($invoice->user->phone)
                    {{ $invoice->user->phone }}
                @endif
                 {{ $invoice->user->billing_address ?? 'N/A' }}<br>
                {{ $invoice->user->email ?? 'N/A' }}
            </div>
            </td>

            <!-- RIGHT SIDE: Invoice Info + Status -->
            <td width="50%" style="vertical-align: top; text-align: right; line-height: 1.6;">
                <h2 style="margin: 0 0 10px; font-size: 24px;">INVOICE</h2>

                <p style="margin: 4px 0;"><strong>Invoice #</strong> {{ $invoice->chargebee_invoice_id }}</p>
                <p style="margin: 4px 0;"><strong>Order #</strong> {{ $invoice->order->id }}</p>
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

    <!-- Items Table -->
    <table class="items-table" width="100%" cellpadding="10" cellspacing="0" border="1"
           style="border-collapse: collapse; margin-bottom: 30px;">
        <thead style="background-color: #f5f5f5;">
            <tr>
                <th align="left">Description</th>
                <th>Plan</th>
                <th>Period</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Subscription Service<br>
                    <small style="color: #666;">Order ID: {{ $invoice->order->id }}</small>
                </td>
                <td>{{ optional($invoice->order->plan)->name ?? 'N/A' }}</td>
                <td>
                    Monthly<br>
                    <small style="color: #666;">
                        {{ $invoice->order->created_at->format('M d, Y') }} - 
                        {{ $invoice->order->created_at->addMonth()->format('M d, Y') }}
                    </small>
                </td>
                <td>${{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="total-section" style="width: 100%; margin-bottom: 40px;">
        <table width="100%" style="font-size: 14px;">
            <tr>
                <td align="right" width="80%">Subtotal:</td>
                <td align="right">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            @if(isset($invoice->metadata['tax']))
            <tr>
                <td align="right">Tax:</td>
                <td align="right">${{ number_format($invoice->metadata['tax'], 2) }}</td>
            </tr>
            @endif
            <tr class="total-row" style="font-weight: bold;">
                <td align="right">Total:</td>
                <td align="right">
                    ${{ number_format($invoice->amount + (isset($invoice->metadata['tax']) ? $invoice->metadata['tax'] : 0), 2) }}
                </td>
            </tr>
            @if($invoice->status === 'paid')
            <tr>
                <td align="right">Paid on:</td>
                <td align="right">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('M d, Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Footer -->
    <div class="footer" style="text-align: center; font-size: 12px; color: #666;">
        <p>Thank you for your business!</p>
        @if($invoice->status !== 'paid')
            <p>Please make payment within 30 days of invoice date.</p>
        @endif
        <p style="font-size: 0.8em; color: #999;">
            Invoice ID: {{ $invoice->id }} | Generated on {{ now()->format('M d, Y H:i:s') }}
        </p>
    </div>
</body>

</html>