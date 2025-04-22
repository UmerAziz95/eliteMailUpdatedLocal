<!DOCTYPE html>
<html>
<head>
    <style>
        :root {
            --primary-color: #212433;
            --secondary-color: #2f3349;
            --second-primary: #7367ef;
            --light-color: #ffffffbb;
            --white-color: #fff;
            --box-shadow: rgba(104, 104, 141, 0.214) 0px 2px 5px 0px,
                rgba(148, 148, 148, 0.443) 0px 1px 3px 0px;
            --input-border: #ffffff4c;
        }
        
        body {
            font-family: "Public Sans", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: var(--light-color);
            margin: 0;
            padding: 0;
            background-color: var(--primary-color);
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background-color: var(--secondary-color);
            color: var(--light-color);
            box-shadow: var(--box-shadow);
            border: none;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--input-border);
        }
        .card-header h2 {
            color: var(--white-color);
            font-size: 20px;
            margin: 0;
            font-weight: 600;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-badge.success {
            background-color: rgba(108, 255, 108, 0.302);
            color: rgb(16, 219, 16);
        }
        .status-badge.pending {
            background-color: rgba(255, 166, 0, 0.293);
            color: rgb(255, 150, 22);
        }
        .card-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--input-border);
        }
        .card-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .section-title {
            color: var(--second-primary);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }
        .detail-label {
            color: var(--light-color);
            opacity: 0.7;
        }
        .detail-value {
            color: var(--white-color);
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(270deg, rgba(115, 103, 240, 0.7) 0%, #7367f0 100%);
            color: var(--white-color);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            text-align: center;
        }
        .footer {
            text-align: center;
            color: var(--light-color);
            font-size: 14px;
            opacity: 0.7;
            margin-top: 30px;
        }
        .plan-badge {
            background: linear-gradient(270deg, rgba(115, 103, 240, 0.2) 0%, rgba(115, 103, 240, 0.1) 100%);
            color: var(--second-primary);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        .price-text {
            font-size: 24px;
            color: var(--white-color);
            font-weight: 600;
            margin: 10px 0;
        }
        .price-text .period {
            font-size: 14px;
            opacity: 0.7;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .feature-list li i {
            color: var(--second-primary);
        }
        .highlight-box {
            background: rgba(115, 103, 240, 0.1);
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>{{ $isAdminNotification ? 'New Order Created' : 'Order Confirmation' }}</h2>
                <span class="status-badge {{ $order->status == 'paid' ? 'success' : 'pending' }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>

            @if($isAdminNotification)
                <div class="card-section">
                    <div class="section-title">
                        <i class="fa-regular fa-user"></i>
                        Customer Details
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Name: </span>
                        <span class="detail-value">{{ $user->name }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email: </span>
                        <span class="detail-value">{{ $user->email }}</span>
                    </div>
                </div>
            @else
                <div class="card-section">
                    <p>Dear {{ $user->name }},</p>
                    <p>Thank you for choosing ProjectInbox! Your subscription has been successfully processed.</p>
                </div>
            @endif

            <div class="card-section">
                <div class="section-title">
                    <i class="fa-solid fa-crown"></i>
                    Subscription Plan
                </div>
                <span class="plan-badge">{{ $order->plan->name }}</span>
                <div class="price-text">
                    ${{ number_format($order->amount, 2) }}
                    <span class="period">/ {{ $order->plan->duration }}</span>
                </div>
                @if($order->plan->features->count() > 0)
                <ul class="feature-list">
                    @foreach($order->plan->features as $feature)
                    <li>
                        <i class="fa-solid fa-check"></i>
                        {{ $feature->name }}: {{ $feature->pivot->value }}
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>

            <div class="card-section">
                <div class="section-title">
                    <i class="fa-solid fa-file-invoice"></i>
                    Order Details
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID: </span>
                    <span class="detail-value">{{ $order->chargebee_invoice_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction Date: </span>
                    <span class="detail-value">{{ $order->created_at->format('F d, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status: </span>
                    <span class="detail-value">{{ ucfirst($order->status) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Subscription ID: </span>
                    <span class="detail-value">{{ $order->chargebee_subscription_id }}</span>
                </div>
            </div>

            @if($order->reorderInfo->count() > 0)
            <div class="card-section">
                <div class="section-title">
                    <i class="fa-solid fa-gear"></i>
                    Service Configuration
                </div>
                <div class="highlight-box">
                    <div class="detail-row">
                        <span class="detail-label">Hosting Platform: </span>
                        <span class="detail-value">{{ $order->reorderInfo->first()->hosting_platform }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Inboxes: </span>
                        <span class="detail-value">{{ $order->reorderInfo->first()->total_inboxes }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Inboxes per Domain: </span>
                        <span class="detail-value">{{ $order->reorderInfo->first()->inboxes_per_domain }}</span>
                    </div>
                </div>
            </div>
            @endif

            @if(!$isAdminNotification)
            <div style="text-align: center; margin-top: 30px;">
                <p>Click below to manage your subscription and view complete order details:</p>
                <a href="{{ route('customer.subscriptions.view') }}" class="button">Manage Subscription</a>
            </div>
            @else
            <p>Please review and process this order at your earliest convenience.</p>
            @endif
        </div>

        <div class="footer">
            <p>If you have any questions about your subscription, please don't hesitate to contact our support team.</p>
            <p>© {{ date('Y') }} ProjectInbox. All rights reserved.</p>
        </div>
    </div>
</body>
</html>