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
            --priority-high: rgba(255, 0, 0, 0.2);
            --priority-medium: rgba(255, 166, 0, 0.293);
            --priority-low: rgba(108, 255, 108, 0.302);
            --status-open: rgba(108, 255, 108, 0.302);
            --status-in-progress: rgba(255, 166, 0, 0.293);
            --status-closed: rgba(200, 200, 200, 0.2);
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
        h2 {
            color: var(--second-primary);
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
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
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--input-border);
            animation: slideIn 0.6s ease-out forwards;
        }
        .priority-badge, .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            animation: pulse 2s infinite;
        }
        .priority-badge.high {
            background-color: var(--priority-high);
            color: rgb(255, 80, 80);
        }
        .priority-badge.medium {
            background-color: var(--priority-medium);
            color: rgb(255, 150, 22);
        }
        .priority-badge.low {
            background-color: var(--priority-low);
            color: rgb(16, 219, 16);
        }
        .status-badge.open {
            background-color: var(--status-open);
            color: rgb(16, 219, 16);
        }
        .status-badge.in_progress {
            background-color: var(--status-in-progress);
            color: rgb(255, 150, 22);
        }
        .status-badge.closed {
            background-color: var(--status-closed);
            color: var(--white-color);
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
        .detail-label {
            color: var(--text-secondary);
            opacity: 0.7;
        }
        .detail-value {
            color: var(--white-color);
            font-weight: 500;
        }
        .message-box {
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.12) 100%);
            padding: clamp(20px, 4vw, 30px);
            border-radius: 16px;
            margin: 15px 0;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .reply-meta {
            color: var(--second-primary);
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
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
                transparent 100%
            );
        }
        .footer p {
            margin-bottom: 16px;
            line-height: 1.8;
            font-size: clamp(13px, 1vw, 14px);
        }
        .highlight-text {
            background: linear-gradient(135deg, #ffffff 0%, #7367ef 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
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
        .highlight-box {
            background: rgba(115, 103, 240, 0.08);
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            border: 1px solid rgba(115, 103, 240, 0.15);
            animation: fadeIn 0.8s ease-out forwards;
            animation-delay: 0.4s;
        }
        .attachments-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .attachment-item {
            padding: 12px;
            margin-bottom: 10px;
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.12) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            border: 1px solid rgba(115, 103, 240, 0.15);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out forwards;
        }
        .attachment-item:hover {
            transform: translateX(5px);
            background: linear-gradient(145deg, rgba(115, 103, 240, 0.12) 0%, rgba(115, 103, 240, 0.18) 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .attachment-icon {
            margin-right: 12px;
            color: var(--second-primary);
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        .attachment-link {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            word-break: break-all;
            transition: color 0.3s ease;
            flex: 1;
        }
        .attachment-link:hover {
            color: var(--second-primary);
        }
        .attachment-size {
            color: var(--text-secondary);
            font-size: 12px;
            margin-left: 12px;
            opacity: 0.7;
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
            <h2>New Ticket Reply</h2>
        </div>
        <br>
        <div class="card">
            <!-- <div class="card-header">
                <div style="display: flex; gap: 8px;">
                    <span class="priority-badge {{ strtolower($ticket->priority) }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                    <span class="status-badge {{ str_replace(' ', '_', strtolower($ticket->status)) }}">
                        {{ ucfirst($ticket->status) }}
                    </span>
                </div>
            </div> -->

            <div class="card-section">
                @if($isCustomerReply)
                    <p>Dear {{ $assignedStaff->name }},</p>
                    <p>The customer <strong>{{ $repliedBy->name }}</strong> has responded to support ticket #{{ $ticket->ticket_number }}. Please review their message and provide assistance.</p>
                @else
                    <p>Dear {{ $assignedStaff->name }},</p>
                    <p>{{ $repliedBy->name }} has replied to ticket #{{ $ticket->ticket_number }}.</p>
                @endif
            </div>

            <div class="highlight-box">
                <div class="section-title">
                    <i class="fa-solid fa-ticket"></i>
                    Ticket Details
                </div>
                <div class="detail-row">
                    <span class="detail-label">Ticket Number:</span>
                    <span class="detail-value">#{{ $ticket->ticket_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value">{{ $ticket->subject }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Category:</span>
                    <span class="detail-value">{{ ucfirst($ticket->category) }}</span>
                </div>
                <!-- $ticket->priority -->
                <div class="detail-row">
                    <span class="detail-label">Priority:</span>
                    <span class="detail-value">{{ ucfirst($ticket->priority) }}</span>
                </div>
                <!-- $ticket->status -->
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">{{ ucfirst($ticket->status) }}</span>
                </div>

            </div>

            <div class="card-section">
                <div class="section-title">
                    <i class="fa-solid fa-reply"></i>
                    @if($isCustomerReply)
                        Customer Response
                    @else
                        New Reply
                    @endif
                </div>
                <div class="reply-meta">
                    <i class="fa-solid fa-user"></i>
                    {{ $repliedBy->name }}
                    @if($isCustomerReply)
                        <span style="color: var(--second-primary); font-weight: 500;">(Customer)</span>
                    @endif
                </div>
                <div class="message-box">
                    {!! $reply->message !!}
                </div>
            </div>

            @if($reply->attachments && count($reply->attachments) > 0)
            <div class="card-section">
                <div class="section-title">
                    <i class="fa-solid fa-paperclip"></i>
                    Attachments
                </div>
                <ul class="attachments-list">
                    @foreach($reply->attachments as $attachment)
                    <li class="attachment-item">
                        <i class="fa-solid fa-file attachment-icon"></i>
                        <a href="{{ url('storage/'.$attachment) }}" class="attachment-link" target="_blank">
                            {{ basename($attachment) }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- <div style="text-align: center;">
                <a href="{{ route('admin.support.tickets.show', $ticket->id) }}" class="button">View Full Conversation</a>
            </div> -->

            <div class="footer">
                <p>You can view and respond to this ticket from your support dashboard.</p>
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>