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
        h2 {
            color: var(--second-primary);
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .content {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.1) 0%, rgba(115, 103, 240, 0.05) 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid rgba(115, 103, 240, 0.2);
            animation: borderGlow 2s infinite alternate;
        }
        @keyframes borderGlow {
            from { border-color: rgba(115, 103, 240, 0.1); }
            to { border-color: rgba(115, 103, 240, 0.3); }
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
        .btn-primary {
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
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(115, 103, 240, 0.4);
        }
        .note {
            background: linear-gradient(145deg, rgba(255, 82, 82, 0.1) 0%, rgba(255, 82, 82, 0.05) 100%);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid rgba(255, 82, 82, 0.2);
            color: var(--text-secondary);
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
                margin: 10px;
                padding: 15px;
            }
            .content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Subscription Cancelled</h2>
        
        <p>Dear {{ $user->name }},</p>
        
        <div class="content">
            <p>Your subscription has been cancelled successfully. Here are the details:</p>
            
            <div class="detail-row">
                <span class="detail-label">Plan:</span>
                <span class="detail-value">{{ $subscription->plan->name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Cancellation Date:</span>
                <span class="detail-value">{{ $subscription->cancellation_at ? \Carbon\Carbon::parse($subscription->cancellation_at)->format('F j, Y') : 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">End Date:</span>
                <span class="detail-value">{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('F j, Y') : 'N/A' }}</span>
            </div>
        </div>

        <div class="note">
            Your access to the services will continue until {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('F j, Y') : 'N/A' }}.
        </div>

        <p>If you change your mind or wish to subscribe again in the future, you can always visit our pricing page to select a new plan:</p>
        
        <div style="text-align: center;">
            <a href="{{ route('pricing') }}" class="btn-primary">View Plans</a>
        </div>

        <div class="footer">
            <p>Thank you for being our customer. We hope to see you again soon!</p>
            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
    </div>
</body>
</html>