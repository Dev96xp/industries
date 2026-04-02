<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote {{ $quote->number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f7; padding: 24px; }
        .wrapper { max-width: 680px; margin: 0 auto; }
        .header { background: #1e3a5f; border-radius: 12px 12px 0 0; padding: 24px 32px; }
        .header h1 { color: #fff; font-size: 18px; margin: 0; }
        .header p  { color: #93c5fd; font-size: 13px; margin-top: 4px; }
        .message-box { background: #fff; padding: 28px 32px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .message-box p { font-size: 14px; color: #374151; line-height: 1.7; }
        .approve-bar { background: #eff6ff; border: 1px solid #bfdbfe; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; }
        .approve-bar p { font-size: 13px; color: #6b7280; margin-bottom: 14px; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff !important; font-size: 15px; font-weight: 700; padding: 14px 36px; border-radius: 10px; text-decoration: none; }
        .view-link { display: inline-block; margin-top: 12px; font-size: 13px; color: #2563eb !important; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $company->company_name }}</h1>
            <p>Quote {{ $quote->number }} — Please review and approve</p>
        </div>

        <div class="message-box">
            <p>Hello <strong>{{ $quote->client_name }}</strong>,</p>
            <p style="margin-top:10px;">Please find your quote attached below. Review the details and click the button to approve it.</p>
        </div>

        <div class="approve-bar">
            <p>Ready to move forward? Approve your quote with one click.</p>
            <a href="{{ $approveUrl }}" class="btn" style="color: #ffffff !important; text-decoration: none;">✓ &nbsp; Approve Quote</a>
        </div>

        {{-- Inline quote report --}}
        @include('admin.quotes.report', ['quote' => $quote, 'company' => $company, 'inEmail' => true])
    </div>
</body>
</html>
