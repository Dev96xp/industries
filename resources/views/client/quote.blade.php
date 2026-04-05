<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote {{ $quote->number }} — {{ $quote->client_name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 40px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .company-name { font-size: 20px; font-weight: 700; color: #1e3a5f; }
        .company-sub  { font-size: 11px; color: #888; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.05em; }
        .report-title { text-align: right; }
        .report-title .label  { font-size: 22px; font-weight: 700; color: #1e3a5f; }
        .report-title .number { font-size: 14px; color: #555; margin-top: 2px; font-family: monospace; }
        .meta-section { display: flex; justify-content: space-between; gap: 40px; margin-bottom: 28px; }
        .meta-block h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #888; margin-bottom: 8px; }
        .meta-block p  { font-size: 13px; color: #1a1a1a; line-height: 1.6; }
        .meta-block p.label { font-size: 11px; color: #666; margin-bottom: 2px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-draft    { background: #f4f4f5; color: #71717a; }
        .status-sent     { background: #eff6ff; color: #2563eb; }
        .status-accepted { background: #f0fdf4; color: #16a34a; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #1e3a5f; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        thead th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #888; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; }
        tbody td { padding: 9px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .font-semibold { font-weight: 600; }
        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 16px; margin-bottom: 28px; }
        .totals-table { width: 280px; }
        .totals-table td { padding: 5px 8px; font-size: 13px; }
        .totals-table td:last-child { text-align: right; font-weight: 600; }
        .totals-table tr.separator td { border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .totals-table tr.total-row td { border-top: 2px solid #1e3a5f; font-size: 15px; font-weight: 700; color: #1e3a5f; padding-top: 8px; }
        .text-muted { color: #888; }
        .notes-section { margin-bottom: 24px; }
        .notes-section h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #888; margin-bottom: 6px; }
        .notes-box { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 14px; font-size: 12px; color: #555; line-height: 1.7; white-space: pre-line; }
        .report-footer { border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 10px; color: #aaa; display: flex; justify-content: space-between; }
        .action-bar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; }
        .btn { border: none; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-back  { background: #f4f4f5; color: #374151; }
        .btn-print { background: #1e3a5f; color: #fff; }
        .btn-back:hover  { background: #e5e7eb; }
        .btn-print:hover { background: #162d4a; }
        @media (max-width: 600px) {
            body { padding: 16px; }
            .meta-section { flex-direction: column; gap: 16px; }
            .meta-block[style*="text-align:right"] { text-align: left !important; }
            .totals-wrap { justify-content: flex-start; }
            .totals-table { width: 100%; }
        }
        @media print {
            body { padding: 20px; }
            .action-bar { display: none; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body>

<div class="action-bar">
    <a href="{{ route('home') }}" class="btn btn-back">← Back to Website</a>
    <a href="{{ route('client.account') }}" class="btn btn-back">My Account</a>
    <button class="btn btn-print" onclick="window.print()">🖨 Print</button>
</div>

{{-- Header --}}

<div class="report-header">
    <div>
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="company-sub">Construction Group</div>
    </div>
    <div class="report-title">
        <div class="label">QUOTE</div>
        <div class="number">{{ $quote->number }}</div>
    </div>
</div>

{{-- Client & Quote Meta --}}
<div class="meta-section">
    <div class="meta-block">
        <h4>Bill To</h4>
        <p style="font-weight:700">{{ $quote->client_name }}</p>
        @if($quote->client_email) <p>{{ $quote->client_email }}</p> @endif
        @if($quote->client_phone) <p>{{ $quote->client_phone }}</p> @endif
    </div>
    <div class="meta-block" style="text-align:right">
        <h4>Quote Details</h4>
        <p class="label">Date</p>
        <p style="margin-bottom:8px">{{ $quote->quote_date->format('F d, Y') }}</p>
        @if($quote->expiration_date)
            <p class="label">Expires</p>
            <p style="margin-bottom:8px">{{ $quote->expiration_date->format('F d, Y') }}</p>
        @endif
        <p class="label">Status</p>
        <span class="status-badge status-{{ $quote->status }}">{{ ucfirst($quote->status) }}</span>
    </div>
</div>

{{-- Line Items --}}
<div class="section-title">Line Items</div>
<table>
    <thead>
        <tr>
            <th style="width:55%">Description</th>
            <th class="text-right" style="width:15%">Qty</th>
            <th class="text-right" style="width:15%">Unit Price</th>
            <th class="text-right" style="width:15%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quote->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                <td class="text-right">${{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="text-right font-semibold">${{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<div class="totals-wrap">
    <table class="totals-table">
        <tr>
            <td class="text-muted">Subtotal</td>
            <td>${{ number_format($quote->subtotal, 2) }}</td>
        </tr>
        @if((float) $quote->tax_percentage > 0)
            <tr>
                <td class="text-muted">Tax ({{ (float) $quote->tax_percentage }}%)</td>
                <td>${{ number_format($quote->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if((float) $quote->discount > 0)
            <tr>
                <td class="text-muted">Discount</td>
                <td>-${{ number_format((float) $quote->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>Total</td>
            <td>${{ number_format($quote->total, 2) }}</td>
        </tr>
    </table>
</div>

@if($quote->notes)
    <div class="notes-section">
        <h4>Notes</h4>
        <div class="notes-box">{{ $quote->notes }}</div>
    </div>
@endif

@if($quote->terms)
    <div class="notes-section">
        <h4>Terms & Conditions</h4>
        <div class="notes-box">{{ $quote->terms }}</div>
    </div>
@endif

<div class="report-footer">
    <span>{{ $company->company_name }} — Thank you for your business.</span>
    <span>{{ $quote->number }} · {{ now()->format('M d, Y') }}</span>
</div>

</body>
</html>
