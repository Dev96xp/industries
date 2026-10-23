<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $quote->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f7; padding: 24px; }
        .wrapper { max-width: 680px; margin: 0 auto; }
        .header { background: #1e3a5f; border-radius: 12px 12px 0 0; padding: 24px 32px; }
        .header h1 { color: #fff; font-size: 18px; margin: 0; }
        .header p  { color: #93c5fd; font-size: 13px; margin-top: 4px; }
        .message-box { background: #fff; padding: 28px 32px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; }
        .message-box p { font-size: 14px; color: #374151; line-height: 1.7; }
        .pay-bar { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; }
        .pay-bar .amount { font-size: 26px; font-weight: 700; color: #1e3a5f; }
        .pay-bar p { font-size: 13px; color: #6b7280; margin: 4px 0 14px; }
        .btn { display: inline-block; background: #16a34a; color: #ffffff !important; font-size: 15px; font-weight: 700; padding: 14px 36px; border-radius: 10px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $company->company_name }}</h1>
            <p>Invoice {{ $quote->invoice_number }} — Due {{ $quote->invoice_due_date->format('F d, Y') }}</p>
        </div>

        <div class="message-box">
            <p>Hello <strong>{{ $quote->client_name }}</strong>,</p>
            @if($quote->title)
                <p style="margin-top:10px;">Job: <strong>{{ $quote->title }}</strong></p>
            @endif
            <p style="margin-top:10px;">Thank you for approving quote {{ $quote->number }}. Please find your invoice below.</p>
        </div>

        @if($quote->balance_due > 0)
            <div class="pay-bar">
                <div class="amount">${{ number_format($quote->balance_due, 2) }}</div>
                <p>Balance due by {{ $quote->invoice_due_date->format('F d, Y') }}</p>
                @if($payUrl)
                    <a href="{{ $payUrl }}" class="btn" style="color: #ffffff !important; text-decoration: none;">💳 &nbsp; Pay Now</a>
                @endif
            </div>
        @else
            <div class="pay-bar">
                <p style="margin:0; font-weight:700; color:#16a34a;">✓ This invoice has been paid in full. Thank you!</p>
            </div>
        @endif

        {{-- Inline invoice report --}}
        @include('admin.quotes.report', ['quote' => $quote, 'company' => $company, 'inEmail' => true])
    </div>
</body>
</html>
