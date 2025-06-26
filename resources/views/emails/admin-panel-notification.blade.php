<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Creation Required</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #dee2e6;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .stats {
            background-color: white;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .stats h3 {
            margin-top: 0;
            color: #007bff;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background-color: white;
        }
        .order-table th,
        .order-table td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        .order-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 Urgent: New Panels Required</h1>
    </div>
      <div class="content">
        <div class="alert">
            <strong>Panel Status Report:</strong> This is your regular panel capacity monitoring report.
        </div>
        
        <div class="stats">
            <h3>Current Statistics</h3>
            <p><strong>Total Pending Inboxes:</strong> {{ number_format($totalInboxes) }}</p>
            <!-- <p><strong>Available Panel Space:</strong> {{ number_format($availableSpace) }}</p> -->
            @if($panelsNeeded > 0)
                <p><strong>Panels Needed:</strong> {{ $panelsNeeded }}</p>
                <p><strong>Status:</strong> <span style="color: #dc3545; font-weight: bold;">⚠️ ACTION REQUIRED</span></p>
            @else
                <p><strong>Panels Needed:</strong> 0</p>
                <p><strong>Status:</strong> <span style="color: #28a745; font-weight: bold;">✅ CAPACITY SUFFICIENT</span></p>
            @endif
            <p><strong>Panel Capacity:</strong> 1,790 inboxes per panel</p>
        </div>
        
        @if($panelsNeeded > 0)
        <h3>Required Action</h3>
        <p>Please create <strong>{{ $panelsNeeded }} new panel(s)</strong> to ensure sufficient capacity for the pending inbox allocations.</p>
        
        @if(!empty($insufficientSpaceOrders))
        <h4>Orders Requiring Immediate Attention:</h4>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Required Space (Inboxes)</th>
                    <!-- <th>Available Space</th> -->
                    <th>Panels Needed</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insufficientSpaceOrders as $order)
                <tr>
                    <td>{{ $order['order_id'] }}</td>
                    <td>{{ number_format($order['required_space']) }}</td>
                    <!-- <td>{{ number_format($order['available_space']) }}</td> -->
                    <td><strong>{{ $order['panels_needed'] }}</strong></td>
                    <td><span style="color: #dc3545;">{{ strtoupper($order['status']) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Summary:</strong> {{ count($insufficientSpaceOrders) }} orders cannot be processed due to insufficient panel space. Total {{ $panelsNeeded }} panels needed.</p>
        @endif
        
        @else
        <h3>No Action Required</h3>
        <p>Current panel capacity is sufficient to handle all pending inbox requests.</p>
        @endif
        
        <p>Each panel can accommodate up to 1,790 inboxes. The system automatically calculated this requirement based on current pending orders.</p>
        
        <div class="footer">
            <p>This is an automated notification from the Panel Management System.</p>
            <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
