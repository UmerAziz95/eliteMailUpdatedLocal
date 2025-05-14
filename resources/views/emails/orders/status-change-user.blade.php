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
            --input-border: rgba(255, 255, 255, 0.3);
            --gradient-1: linear-gradient(135deg, #7367ef 0%, #8f84ff 100%);
            --gradient-2: linear-gradient(45deg, rgba(115, 103, 240, 0.15) 0%, rgba(115, 103, 240, 0.05) 100%);
            --gradient-3: linear-gradient(to right, #7367ef 0%, #6254e8 100%);
            --bg-gradient-1: linear-gradient(135deg, #1a1f2c 0%, #212433 100%);
            --bg-gradient-2: linear-gradient(45deg, rgba(47, 51, 73, 0.98) 0%, rgba(33, 36, 51, 0.98) 100%);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Public Sans", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: var(--bg-gradient-1);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: min(90%, 800px);
            margin: 40px auto;
            background: var(--bg-gradient-2);
            padding: clamp(20px, 5vw, 40px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out forwards;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.12) 100%);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .welcome-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin: -20px -20px 40px -20px;
            padding: 40px;
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.15) 0%, rgba(115, 103, 240, 0.05) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            animation: slideIn 0.6s ease-out forwards;
            border-radius: 15px;
        }
        .welcome-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, 
                transparent 0%, 
                rgba(255, 255, 255, 0.2) 50%,
                transparent 100%
            );
        }
        .welcome-header h2 {
            color: var(--white-color);
            font-size: 32px;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #ffffff 0%, #7367ef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 10px rgba(115, 103, 240, 0.3);
            position: relative;
            z-index: 1;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--input-border);
            animation: slideIn 0.6s ease-out forwards;
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .status-badge.success {
            background: linear-gradient(145deg, rgba(16, 219, 16, 0.1) 0%, rgba(16, 219, 16, 0.2) 100%);
            color: rgb(16, 219, 16);
            border: 1px solid rgba(16, 219, 16, 0.2);
        }
        .status-badge.warning {
            background: linear-gradient(145deg, rgba(255, 150, 22, 0.1) 0%, rgba(255, 150, 22, 0.2) 100%);
            color: rgb(255, 150, 22);
            border: 1px solid rgba(255, 150, 22, 0.2);
        }
        .status-badge.danger {
            background: linear-gradient(145deg, rgba(255, 80, 80, 0.1) 0%, rgba(255, 80, 80, 0.2) 100%);
            color: rgb(255, 80, 80);
            border: 1px solid rgba(255, 80, 80, 0.2);
        }
        .card-section {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.12) 100%);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 16px;
            margin: 25px 0;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.2s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .highlight-box {
            background: rgba(115, 103, 240, 0.08);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid rgba(115, 103, 240, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.4s;
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
            animation: slideIn 0.4s ease-out forwards;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-secondary);
            opacity: 0.7;
        }
        .detail-value {
            color: var(--white-color);
            font-weight: 500;
        }
        .button {
            display: inline-block;
            background: var(--gradient-3);
            color: var(--white-color);
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
            border: none;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(115, 103, 240, 0.4);
        }
        .footer {
            margin: 40px 0 0 0;
            padding: 32px 40px;
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.15) 0%, rgba(115, 103, 240, 0.05) 100%);
            text-align: center;
            font-size: 14px;
            color: var(--text-secondary);
            position: relative;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.8s;
            border-radius: 15px;
            width: 100 !important;
        }
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, 
                transparent 0%, 
                rgba(255, 255, 255, 0.2) 50%,
                transparent 100%
            );
        }
        .footer p {
            margin-bottom: 16px;
            line-height: 1.8;
            font-size: clamp(13px, 1vw, 14px);
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 95%;
                margin: 10px;
                padding: 15px;
            }
            .card-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-header">
            <h2>Order Status Update</h2>
        </div>
        <br>
        <div class="card">
            <div class="card-header">
                <div class="section-title">Order #{{ $order->id }}</div>
                <!-- <span class="status-badge {{ $newStatus == 'completed' ? 'success' : ($newStatus == 'rejected' ? 'danger' : 'warning') }}">
                    {{ ucfirst($newStatus) }}
                </span> -->
            </div>

            <div class="card-section">
                <p>Dear {{ $user->name }},</p>
                <p>We're writing to inform you that your order status has been updated.</p>
                <br>
                <span class="status-badge {{ $newStatus == 'completed' ? 'success' : ($newStatus == 'rejected' ? 'danger' : 'warning') }}">
                    {{ ucfirst($newStatus) }}
                </span>
            </div>

            <div class="highlight-box">
                <div class="section-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Status Change Details
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

            <!-- <div style="text-align: center;">
                <a href="{{ route('customer.orders.view', $order->id) }}" class="button">View Order Details</a>
            </div> -->

            <div class="footer">
                <p>You can view your order details anytime from your dashboard.</p>
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>