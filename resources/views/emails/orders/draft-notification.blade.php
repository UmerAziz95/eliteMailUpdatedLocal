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
            --white-color: #ffffff;
            --box-shadow: rgba(104, 104, 141, 0.214) 0px 2px 5px 0px,
                rgba(148, 148, 148, 0.443) 0px 1px 3px 0px;
            --input-border: rgba(255, 255, 255, 0.3);
            --gradient-1: linear-gradient(135deg, #7367ef 0%, #8f84ff 100%);
            --gradient-2: linear-gradient(45deg, rgba(115, 103, 240, 0.15) 0%, rgba(115, 103, 240, 0.05) 100%);
            --gradient-3: linear-gradient(to right, #7367ef 0%, #6254e8 100%);
            --bg-gradient-1: linear-gradient(135deg, #1a1f2c 0%, #212433 100%);
            --bg-gradient-2: linear-gradient(45deg, rgba(47, 51, 73, 0.98) 0%, rgba(33, 36, 51, 0.98) 100%);
            --warning-color: #ff9800;
            --warning-gradient: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes borderGlow {
            0% { box-shadow: 0 0 0 0 rgba(115, 103, 240, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(115, 103, 240, 0); }
            100% { box-shadow: 0 0 0 0 rgba(115, 103, 240, 0); }
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
        .welcome-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin: -20px -20px 40px -20px;
            padding: 40px;
            background: linear-gradient(145deg, rgba(255, 152, 0, 0.15) 0%, rgba(255, 152, 0, 0.05) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            animation: slideIn 0.6s ease-out forwards;
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
            background: linear-gradient(135deg, #ffffff 0%, #ff9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 10px rgba(255, 152, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        .content {
            background: linear-gradient(145deg, rgba(255, 152, 0, 0.08) 0%, rgba(255, 152, 0, 0.12) 100%);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 16px;
            margin: 25px 0;
            color: var(--text-primary);
            border: 1px solid rgba(255, 152, 0, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.2s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .alert-banner {
            background: var(--warning-gradient);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: pulse 2s infinite;
        }
        .alert-banner h3 {
            color: var(--white-color);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .alert-banner p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        .detail-card {
            background: rgba(255, 152, 0, 0.08);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid rgba(255, 152, 0, 0.15);
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .detail-label {
            color: var(--second-primary);
            font-weight: 500;
            font-size: 14px;
        }
        .detail-value {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }
        .status-draft {
            color: var(--warning-color);
            background: rgba(255, 152, 0, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .btn-primary {
            display: inline-block;
            background: var(--gradient-3);
            color: var(--white-color);
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 25px 0;
            border: none;
            text-align: center;
            box-shadow: 0 4px 15px rgba(115, 103, 240, 0.2);
            transition: all 0.3s ease;
            animation: borderGlow 2s infinite;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(115, 103, 240, 0.3);
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
        .highlight-text {
            background: linear-gradient(135deg, #ffffff 0%, #7367ef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        .warning-text {
            background: linear-gradient(135deg, #ffffff 0%, #ff9800 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        @media only screen and (max-width: 768px) {
            body {
                background: var(--primary-color);
            }
            .container {
                width: 100%;
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
            }
            .btn-primary {
                width: 100%;
                text-align: center;
                padding: 18px 24px;
            }
            .welcome-header {
                padding: 30px 20px;
            }
            .welcome-header h2 {
                font-size: clamp(20px, 5vw, 24px);
            }
            .content {
                border-radius: 12px;
            }
            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-header">
            <h2>⚠️ Action Required</h2>
        </div>
        
        <div class="content">
            <div class="alert-banner">
                <h3>Draft Order Pending</h3>
                <p>This order has been in draft status and requires your attention</p>
            </div>

            <p>Dear {{ $user->name }},</p>
            <p>Your order <strong>#{{ $order->chargebee_invoice_id ?? $order->id }}</strong> has been in draft status for some time and requires your attention to proceed.</p>
            <p>Please review your order details and take the necessary action to complete or update your order status.</p>

            <div class="detail-card">
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">#{{ $order->chargebee_invoice_id ?? $order->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">{{ $user->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $user->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Plan:</span>
                    <span class="detail-value">{{ $order->plan->name ?? "N/A" }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status-draft">{{ ucfirst($order->status_manage_by_admin) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value">${{ number_format($order->amount, 2) }} {{ $order->currency }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                </div>
            </div>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <center>
                <a href="{{ config('app.url') }}/orders/{{ $order->id }}" class="btn-primary">
                    View Order Details
                </a>
            </center>
        </div>

        <div class="footer">
            <p>This is an automated reminder for order management.</p>
            <p>Best regards,<br>The <span class="highlight-text">{{ config('app.name') }}</span> Team</p>
            <p>© {{ date('Y') }} <span class="highlight-text">{{ config('app.name') }}</span>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
            --second-primary: #7367ef;
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.95);
            --white-color: #ffffff;
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
            background: var(--bg-gradient-1);
            color: var(--text-primary);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--bg-gradient-2);
            border-radius: 16px;
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header {
            background: var(--gradient-1);
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header h1 {
            color: var(--white-color);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 400;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-banner {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .alert-banner h2 {
            color: var(--white-color);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .alert-banner p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        .order-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .order-details h3 {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .order-details h3:before {
            content: "📋";
            margin-right: 10px;
            font-size: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }
        .detail-value {
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 600;
        }
        .status-draft {
            color: #f44336;
            background: rgba(244, 67, 54, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .message-section {
            margin-bottom: 30px;
        }
        .message-section h3 {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .message-section p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        .action-button {
            display: inline-block;
            background: var(--gradient-1);
            color: var(--white-color);
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 15px;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(115, 103, 240, 0.3);
        }
        .footer {
            background: rgba(255, 255, 255, 0.03);
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .footer p {
            color: var(--text-secondary);
            font-size: 12px;
            margin-bottom: 8px;
        }
        .footer .company-name {
            color: var(--second-primary);
            font-weight: 600;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .email-container {
                border-radius: 8px;
            }
            .header {
                padding: 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .content {
                padding: 25px 20px;
            }
            .order-details {
                padding: 20px;
            }
            .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⚠️ Action Required</h1>
            <p>Your order needs attention</p>
        </div>
        <div class="content">
            <div class="alert-banner">
                <h2>Draft Order Pending</h2>
                <p>This order has been in draft status and requires your attention</p>
            </div>

            <div class="order-details">
                <h3>Order Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">#{{ $order->chargebee_invoice_id ?? $order->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">{{ $user->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $user->email }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value status-draft">{{ ucfirst($order->status_manage_by_admin) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value">${{ number_format($order->amount, 2) }} {{ $order->currency }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                </div>
            </div>

            <div class="message-section">
                <h3>What happens next?</h3>
                <p>Dear {{ $user->name }},</p>
                <p>Your order <strong>#{{ $order->id }}</strong> has been in draft status for some time and requires your attention to proceed.</p>
                <p>Please review your order details and take the necessary action to complete or update your order status.</p>
                <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated reminder for order management.</p>
            <p>© {{ date('Y') }} <span class="company-name">{{ config('app.name') }}</span>. All rights reserved.</p>
            <p>If you no longer wish to receive these emails, please contact support.</p>
        </div>
    </div>
</body>
</html>
