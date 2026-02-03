<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Import Failure Alert</title>
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
            background: #f44336;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .detail-row {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-left: 3px solid #f44336;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        .error-box {
            background: #ffebee;
            border: 1px solid #f44336;
            padding: 15px;
            margin-top: 20px;
            border-radius: 3px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin: 0;">⚠️ Import Process Failed</h2>
    </div>
    
    <div class="content">
        <p>An import process has failed. Please review the details below:</p>
        
        <div class="detail-row">
            <span class="label">Filename:</span> {{ $data['filename'] }}
        </div>
        
        <div class="detail-row">
            <span class="label">Status:</span> <strong style="color: #f44336;">{{ $data['status'] }}</strong>
        </div>
        
        <div class="detail-row">
            <span class="label">Started At:</span> {{ $data['started_at'] }}
        </div>
        
        <div class="detail-row">
            <span class="label">Finished At:</span> {{ $data['finished_at'] }}
        </div>
        
        <div class="detail-row">
            <span class="label">Total Products:</span> {{ $data['total_products'] }}
        </div>
        
        <div class="detail-row">
            <span class="label">Imported Products:</span> {{ $data['imported_products'] }}
        </div>
        
        <div class="detail-row">
            <span class="label">Failed Products:</span> {{ $data['failed_products'] }}
        </div>
        
        @if($data['error_message'])
        <div class="error-box">
            <strong>Error Message:</strong><br>
            {{ $data['error_message'] }}
        </div>
        @endif
    </div>
    
    <div class="footer">
        <p>This is an automated alert from Fitness Foods Parser API.</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>
