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

        .content p {
            margin-bottom: 16px;
            line-height: 1.8;
            font-size: clamp(14px, 1.1vw, 16px);
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

        .highlight-text {
            background: linear-gradient(135deg, #ffffff 0%, #7367ef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
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
            width: 100%;
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

        .footer .highlight-text {
            font-size: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #7367ef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }        @media only screen and (max-width: 768px) {
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
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="welcome-header">
            <h2>Verify Your Email Address</h2>
        </div>

            <div class="content">

                <p>Dear {{ $user->name }},</p>

                <p>Thank you for registering with <strong>{{ config('app.name') }}</strong>! To complete your sign-up, please verify your email address by clicking the button below.</p>


                {{-- <p>Alternatively, you can verify your email using the following secure link:</p> --}}

                @if($verificationLink)
                <div style="text-align: center; margin: 30px 0;">
                    <a href="{{ $verificationLink }}" class="btn-primary">
                        Verify Email Address
                    </a>
                </div>
                @endif

                <p>If you didn’t request this verification, you can safely ignore this email.</p>
            </div>

            <div class="footer">
                <p>If you have any questions or need help, feel free to contact us at 
                    <a href="mailto:support@projectinbox.ai" style="color: var(--second-primary); text-decoration: none;">
                        support@projectinbox.ai
                    </a>
                </p>
                <p>Best regards,<br>The <span class="highlight-text">{{ config('app.name') }}</span> Team</p>
            </div>

        </div>
</body>

</html>