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
<body>
    <div class="invoice-header">
        <h1>INVOICE</h1>
        <div class="invoice-reference">
            <p>Invoice #{{ $invoice->chargebee_invoice_id }}</p>
            <p>Order #{{ $invoice->order->id }}</p>
        </div>
    </div>

    <div class="invoice-info">
        <table>
            <tr>
                <td>
                    <strong>From:</strong><br>
                    Your Company Name<br>
                    123 Business Street<br>
                    Business City, 12345<br>
                    contact@yourcompany.com
                </td>
                <td>
                    <strong>Bill To:</strong><br>
                    {{ $invoice->user->name }}<br>
                    {{ $invoice->user->email }}<br>
                    @if($invoice->user->phone)
                        {{ $invoice->user->phone }}<br>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Invoice Date:</strong><br>
                    {{ $invoice->created_at->format('M d, Y') }}
                </td>
                <td>
                    <strong>Payment Status:</strong><br>
                    <span style="color: {{ $invoice->status === 'paid' ? '#28a745' : '#ffc107' }}; font-weight: bold;">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
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

    <div class="total-section">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            @if(isset($invoice->metadata['tax']))
            <tr>
                <td>Tax:</td>
                <td>${{ number_format($invoice->metadata['tax'], 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td>${{ number_format($invoice->amount + (isset($invoice->metadata['tax']) ? $invoice->metadata['tax'] : 0), 2) }}</td>
            </tr>
            @if($invoice->status === 'paid')
            <tr>
                <td>Paid on:</td>
                <td>{{ \Carbon\Carbon::parse($invoice->paid_at)->format('M d, Y') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p>Thank you for your business!</p>
        @if($invoice->status !== 'paid')
        <p>Please make payment within 30 days of invoice date.</p>
        @endif
        <p style="font-size: 0.8em; color: #999;">Invoice ID: {{ $invoice->id }} | Generated on {{ now()->format('M d, Y H:i:s') }}</p>
    </div>
</body>
</html>