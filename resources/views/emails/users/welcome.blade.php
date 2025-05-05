<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: "Public Sans", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
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
        .welcome-header {
            margin-bottom: 20px;
            color: #7367ef;
        }
        .content {
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="welcome-header">Welcome to {{ config('app.name') }}! 👋</h2>
        
        <div class="content">
            <p>Dear {{ $user->name }},</p>
            
            <p>Thank you for joining {{ config('app.name') }}! We're excited to have you on board.</p>
            
            <p>Your account has been successfully created with the following details:</p>
            <ul>
                <li>Name: {{ $user->name }}</li>
                <li>Email: {{ $user->email }}</li>
            </ul>

            <p>You can now log in to your account and start exploring our services.</p>
        </div>

        <div class="footer">
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>
            The {{ config('app.name') }} Team</p>
        </div>
    </div>
</body>
</html>