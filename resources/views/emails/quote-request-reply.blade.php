<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Request Reply</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f7; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #2563eb; padding: 28px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; }
        .header p { color: #bfdbfe; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .greeting { font-size: 16px; color: #111827; margin-bottom: 16px; }
        .message { font-size: 15px; color: #374151; line-height: 1.7; white-space: pre-wrap; background: #f9fafb; border-left: 4px solid #2563eb; padding: 16px 20px; border-radius: 0 8px 8px 0; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .footer { padding: 20px 32px; background: #f9fafb; text-align: center; font-size: 12px; color: #9ca3af; }
        .company { font-weight: 600; color: #6b7280; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $company->company_name }}</h1>
            <p>Response to your quote request</p>
        </div>
        <div class="body">
            <p class="greeting">Hello {{ $quoteRequest->name }},</p>
            <div class="message">{{ $replyMessage }}</div>
            <hr class="divider">
            <p style="font-size:13px; color:#6b7280;">
                This is a reply to your quote request submitted on {{ $quoteRequest->created_at->format('M d, Y') }}.
                @if($company->phone) You can also reach us at <strong>{{ $company->phone }}</strong>. @endif
            </p>
        </div>
        <div class="footer">
            <span class="company">{{ $company->company_name }}</span>
            @if($company->address) &nbsp;·&nbsp; {{ $company->address }}{{ $company->city ? ', '.$company->city : '' }} @endif
            @if($company->email) &nbsp;·&nbsp; {{ $company->email }} @endif
        </div>
    </div>
</body>
</html>
