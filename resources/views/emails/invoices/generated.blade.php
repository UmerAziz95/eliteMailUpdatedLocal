<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isAdminNotification ? 'New Invoice Generated' : 'Your New Invoice is Ready' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
        }
        .invoice-details {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .invoice-details ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .invoice-details li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>{{ $isAdminNotification ? 'New Invoice Generated' : 'Your New Invoice is Ready' }}</h2>
        
        <p>{{ $isAdminNotification ? "A new invoice has been generated for {$user->name}." : 'A new invoice has been generated for your subscription.' }}</p>

        <div class="invoice-details">
            <p><strong>Invoice Details:</strong></p>
            <ul>
                <li>Invoice ID: {{ $invoice->chargebee_invoice_id }}</li>
                <li>Amount: ${{ number_format($invoice->amount, 2) }}</li>
                <li>Status: {{ ucfirst($invoice->status) }}</li>
                <li>Date Generated: {{ $invoice->created_at->format('F j, Y') }}</li>
            </ul>
        </div>

        <p>You can view the full invoice details in your account dashboard.</p>

        @if(!$isAdminNotification)
        <p>If you have any questions about this invoice, please don't hesitate to contact our support team.</p>
        @endif
    </div>
</body>
</html>