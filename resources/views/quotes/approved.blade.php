<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote {{ $quote->number }} — {{ $alreadyApproved ? 'Already Approved' : 'Approved!' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f7; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 480px; width: 100%; overflow: hidden; }
        .top-bar { height: 6px; background: linear-gradient(to right, #2563eb, #60a5fa); }
        .body { padding: 40px 32px; text-align: center; }
        .icon { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px; }
        .icon.success { background: #dcfce7; }
        .icon.info    { background: #dbeafe; }
        h1 { font-size: 22px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        p  { font-size: 14px; color: #6b7280; line-height: 1.6; }
        .details { background: #f9fafb; border-radius: 10px; padding: 16px 20px; margin: 24px 0; text-align: left; }
        .details-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
        .details-row .label { color: #9ca3af; }
        .details-row .value { font-weight: 600; color: #111827; }
        .company { margin-top: 28px; font-size: 12px; color: #9ca3af; }
        .company strong { color: #6b7280; }
    </style>
</head>
<body>
    <div class="card">
        <div class="top-bar"></div>
        <div class="body">
            @if($alreadyApproved)
                <div class="icon info">✅</div>
                <h1>Already Approved</h1>
                <p>Quote <strong>{{ $quote->number }}</strong> was already approved. No action needed.</p>
            @else
                <div class="icon success">🎉</div>
                <h1>Quote Approved!</h1>
                <p>Thank you, <strong>{{ $quote->client_name }}</strong>! Your approval has been received and we'll be in touch shortly.</p>
            @endif

            <div class="details">
                <div class="details-row">
                    <span class="label">Quote Number</span>
                    <span class="value">{{ $quote->number }}</span>
                </div>
                <div class="details-row">
                    <span class="label">Total</span>
                    <span class="value">${{ number_format($quote->total, 2) }}</span>
                </div>
                @if($quote->expiration_date)
                <div class="details-row">
                    <span class="label">Valid Until</span>
                    <span class="value">{{ $quote->expiration_date->format('M d, Y') }}</span>
                </div>
                @endif
            </div>

            <div class="company">
                <strong>{{ $company->company_name }}</strong>
                @if($company->phone) &nbsp;·&nbsp; {{ $company->phone }} @endif
                @if($company->email) &nbsp;·&nbsp; {{ $company->email }} @endif
            </div>
        </div>
    </div>
</body>
</html>
