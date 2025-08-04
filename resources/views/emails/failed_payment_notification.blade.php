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
            padding: clamp(25px, 5vw, 45px);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out forwards;
            border: 1px solid rgba(255, 255, 255, 0.1);
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

        .welcome-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin: -25px -25px 35px -25px;
            padding: 35px;
            background: linear-gradient(145deg, rgba(255, 82, 82, 0.15) 0%, rgba(255, 82, 82, 0.05) 100%);
            border-bottom: 1px solid rgba(255, 82, 82, 0.2);
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
                    rgba(255, 82, 82, 0.3) 50%,
                    transparent 100%);
        }

        .welcome-header h2 {
            color: var(--white-color);
            font-size: 32px;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #ffffff 0%, #ff5252 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 10px rgba(255, 82, 82, 0.3);
            position: relative;
            z-index: 1;
        }

        .content {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.12) 100%);
            padding: clamp(25px, 4vw, 35px);
            border-radius: 16px;
            margin: 30px 0;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.2s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 15px !important;
        }

        .section-title {
            color: var(--second-primary);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.4s ease-out forwards;
        }

        .section-title i {
            font-size: 20px;
            opacity: 0.9;
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
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .detail-label {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .detail-value {
            color: var(--white-color);
            font-weight: 500;
        }

        .reason-box {
            background: linear-gradient(145deg, rgba(255, 82, 82, 0.1) 0%, rgba(255, 82, 82, 0.05) 100%);
            padding: clamp(25px, 4vw, 35px);
            border-radius: 16px;
            margin: 30px 0;
            border: 1px solid rgba(255, 82, 82, 0.2);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.4s;
            color: var(--text-secondary);
        }

        .reason-box .section-title {
            color: #ff5252;
            margin-bottom: 15px;
        }

        .footer {
            margin: 35px 0 0 0;
            padding: 35px 40px;
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.15) 0%, rgba(115, 103, 240, 0.05) 100%);
            text-align: center;
            font-size: 14px;
            color: var(--text-secondary);
            position: relative;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.8s;
            border-radius: 15px;
            width: 100% !important;
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

            .content {
                padding: 15px;
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
            <h2>Payment Failed</h2>
        </div>
        <br>
        <div class="content">
            <div class="section-title">
                <i class="fa-regular fa-user"></i>
                Customer Details
            </div>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value">{{ $user->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $user->email }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value">{{ $user->phone }}</span>
            </div>
        </div>
        <br>
        <div class="content">
            <div class="section-title">
                <i class="fa-solid fa-crown"></i>
                Payment Details
            </div>
            <div class="detail-row">
                <span class="detail-label">Subscription ID:</span>
                <span class="detail-value">{{ $failure->chargebee_subscription_id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Failure Type:</span>
                <span class="detail-value">{{ $failure->type }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Failure Date:</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($failure->created_at)->format('F j, Y - h:i A')
                    }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">{{ $failure->status ?? 'Pending' }}</span>
            </div>
        </div>

        <div class="reason-box">
            <div class="section-title">
                <i class="fa-solid fa-circle-exclamation"></i>
                What You Should Do
            </div>
            <p>
                It looks like we were unable to process your recent payment. Please log in to your account and update
                your payment information to avoid service interruption.
            </p>
            <br>
            <p>
                <a href="{{ url('/customer/invoices') }}" target="_blank"
                    style="background-color: #007bff; padding: 10px 20px; color: #fff; text-decoration: none; border-radius: 4px;">
                    Update Payment Information
                </a>
            </p>
        </div>

        <div class="footer">
            <p>Best regards,<br>{{ config('app.name') }}</p>
        </div>
    </div>
</body>

</html>