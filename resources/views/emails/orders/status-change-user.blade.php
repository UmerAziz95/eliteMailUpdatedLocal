<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <style>
        :root {
            --primary-color: #212433;
            --secondary-color: #2f3349;
            --second-primary: #7367ef;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.95);
            --light-color: #ffffffbb;
            --white-color: #fff;
            --box-shadow: rgba(104, 104, 141, 0.214) 0px 2px 5px 0px,
                rgba(148, 148, 148, 0.443) 0px 1px 3px 0px;
            --input-border: #ffffff4c;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            margin: 20px auto;
            padding: 30px;
            background: linear-gradient(45deg, rgba(47, 51, 73, 0.98) 0%, rgba(33, 36, 51, 0.98) 100%);
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card {
            background-color: var(--secondary-color);
            color: var(--light-color);
            box-shadow: var(--box-shadow);
            border: none;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            animation: borderGlow 2s infinite alternate;
        }
        @keyframes borderGlow {
            from { border: 1px solid rgba(255, 255, 255, 0.1); }
            to { border: 1px solid rgba(115, 103, 240, 0.3); }
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
            color: var(--second-primary);
            font-size: 24px;
            margin: 0;
            font-weight: 600;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .status-badge.success {
            background-color: rgba(108, 255, 108, 0.302);
            color: rgb(16, 219, 16);
        }
        .status-badge.warning {
            background-color: rgba(255, 166, 0, 0.293);
            color: rgb(255, 150, 22);
        }
        .status-badge.danger {
            background-color: rgba(255, 0, 0, 0.2);
            color: rgb(255, 80, 80);
        }
        .card-section {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.1) 0%, rgba(115, 103, 240, 0.05) 100%);
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid rgba(115, 103, 240, 0.2);
        }
        .highlight-box {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
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
            margin: 12px 0;
            padding: 12px 0;
            border-bottom: 1px solid var(--input-border);
        }
        .detail-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .detail-label {
            color: var(--light-color);
            opacity: 0.7;
        }
        .detail-value {
            color: var(--white-color);
            font-weight: 500;
        }
        .button {
            display: inline-block;
            background: linear-gradient(270deg, rgba(115, 103, 240, 0.7) 0%, #7367f0 100%);
            color: var(--white-color);
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
            border: none;
            transition: all 0.3s ease;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(115, 103, 240, 0.4);
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--input-border);
            text-align: center;
            color: var(--light-color);
            opacity: 0.7;
            font-size: 14px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100%;
                padding: 15px;
                margin: 10px;
            }
            .card-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Order Status Update</h2>
                <span class="status-badge {{ $newStatus == 'completed' ? 'success' : ($newStatus == 'rejected' ? 'danger' : 'warning') }}">
                    {{ ucfirst($newStatus) }}
                </span>
            </div>

            <div class="card-section">
                <p>Dear {{ $user->name }},</p>
                <p>We're writing to inform you that your order status has been updated.</p>
            </div>

            <div class="highlight-box">
                <div class="section-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Status Change Details
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">#{{ $order->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Previous Status:</span>
                    <span class="detail-value">{{ ucfirst($oldStatus) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">New Status:</span>
                    <span class="detail-value">{{ ucfirst($newStatus) }}</span>
                </div>
                @if($newStatus == 'rejected' && isset($reason))
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">{{ $reason }}</span>
                </div>
                @endif
            </div>

            @if(isset($order->reorderInfo) && $order->reorderInfo->count() > 0)
            <div class="highlight-box">
                <div class="section-title">
                    <i class="fa-solid fa-box"></i>
                    Order Details
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Inboxes:</span>
                    <span class="detail-value">{{ $order->reorderInfo->first()->total_inboxes }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Inboxes per Domain:</span>
                    <span class="detail-value">{{ $order->reorderInfo->first()->inboxes_per_domain }}</span>
                </div>
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ route('customer.orders.view', $order->id) }}" class="button">View Order Details</a>
            </div>

            <div class="footer">
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>