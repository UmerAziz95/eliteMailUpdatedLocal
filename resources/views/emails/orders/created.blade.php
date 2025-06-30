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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes borderGlow {
            0% {
                box-shadow: 0 0 0 0 rgba(115, 103, 240, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(115, 103, 240, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(115, 103, 240, 0);
            }
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
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.15) 0%, rgba(115, 103, 240, 0.05) 100%);
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
                    transparent 100%);
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

        .content {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.12) 100%);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 16px;
            margin: 25px 0;
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.2s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .detail-card {
            background: rgba(115, 103, 240, 0.08);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid rgba(115, 103, 240, 0.15);
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
                    transparent 100%);
        }

        .highlight-text {
            background: linear-gradient(135deg, #ffffff 0%, #7367ef 100%);
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
        {{-- <div class="welcome-header"> --}}
            <div style="text-align: center " class="text-center w-100 bg-info mb-3">
                <h2 class="text-center" style="text-align: center">Order Successful!</h2>
            </div>

          <div class="content" style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
    
    <!-- Logo centered and padded -->
    <div style="text-align: center; padding: 20px 0;">
        <img src="{{ asset('/assets/logo/mail-logo-projectinox.png') }}" alt="Project Inbox Logo" style="max-width: 200px; height: auto;">
    </div>

    <p style="font-size: 16px; color: #999;">Dear {{ $user->name }},</p>

    <!-- isAdminNotification -->
    @if($user->role_id !==1)
    @if($isAdminNotification)
        <p style="font-size: 14px; color: #999;">A new order has been created.</p>
    @else
        <p style="font-size: 14px;color: #999;">Thank you for your order! Your order has been successfully created.</p>
    @endif
    @endif

    <!-- Order Details Table -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; color: #333;">
        <thead>
            <tr>
                <th colspan="2" style="text-align: left; padding: 10px; background-color: #f7f7f7; border: 1px solid #ddd;">
                    Order Details
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">Order Number:</td>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">#{{ $order->id }}</td>
            </tr>
            <tr>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">Plan:</td>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">{{ $order->plan->name ?? "N/A" }}</td>
            </tr>
            <tr>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">Amount:</td>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">${{ number_format($order->amount, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">Payment Status:</td>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">{{ ucfirst($order->status) }}</td>
            </tr>
            <tr>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">Order Status:</td>
                <td style=" color: #999; padding: 10px; border: 1px solid #eee;">{{ ucfirst($order->status_manage_by_admin) }}</td>
            </tr>
            <tr>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">Order Created At:</td>
                <td style="color: #999; padding: 10px; border: 1px solid #eee;">{{ $order->created_at->toString() }}</td>
            </tr>
        </tbody>
    </table>
</div>


            <div class="footer" style="margin-top: 40px; text-align: center; font-size: 13px; color: #999;">
                <p>
                    If you have any questions or need help, feel free to contact at
                    <a href="mailto:support@projectinbox.ai" style="color: #999; text-decoration: underline;">
                        support@projectinbox.ai
                    </a>
                </p>
                <p>
                    Best regards,<br>
                    The <strong>{{ config('app.name') }}</strong> Team
                </p>
            </div>

        </div>
</body>

</html>