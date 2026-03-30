<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report — {{ $project->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
            padding: 40px;
        }

        /* Header */
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
        .report-meta  { text-align: right; font-size: 11px; color: #666; }
        .report-meta strong { display: block; font-size: 13px; color: #1a1a1a; }

        /* Project info */
        .project-title { font-size: 18px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
        .project-meta  { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; font-size: 12px; color: #555; }
        .project-meta span strong { color: #1a1a1a; }

        /* Summary cards */
        .summary { display: flex; gap: 12px; margin-bottom: 28px; }
        .summary-card {
            flex: 1;
            border-radius: 8px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
        }
        .summary-card.income  { background: #f0fdf4; border-color: #bbf7d0; }
        .summary-card.expense { background: #fff7ed; border-color: #fed7aa; }
        .summary-card.net.positive { background: #eff6ff; border-color: #bfdbfe; }
        .summary-card.net.negative { background: #fef2f2; border-color: #fecaca; }
        .summary-card.budget  { background: #f8fafc; border-color: #e2e8f0; }
        .summary-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #888; margin-bottom: 4px; }
        .summary-value { font-size: 20px; font-weight: 700; }
        .summary-card.income  .summary-value { color: #16a34a; }
        .summary-card.expense .summary-value { color: #ea580c; }
        .summary-card.net.positive .summary-value { color: #2563eb; }
        .summary-card.net.negative .summary-value { color: #dc2626; }
        .summary-card.budget  .summary-value { color: #1a1a1a; }

        /* Budget bar */
        .budget-bar-wrap { margin-bottom: 28px; }
        .budget-bar-label { display: flex; justify-content: space-between; font-size: 11px; color: #666; margin-bottom: 5px; }
        .budget-bar-bg { height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .budget-bar-fill { height: 100%; border-radius: 999px; }
        .budget-bar-fill.green  { background: #22c55e; }
        .budget-bar-fill.yellow { background: #f59e0b; }
        .budget-bar-fill.red    { background: #ef4444; }

        /* Tables */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1e3a5f;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        td { padding: 8px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .amount { text-align: right; font-weight: 600; }
        .income-amount  { color: #16a34a; }
        .expense-amount { color: #ea580c; }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }
        .total-row td { font-weight: 700; border-top: 2px solid #e5e7eb; background: #f8fafc; }

        /* Notes */
        .notes-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 28px;
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }

        /* Footer */
        .report-footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            font-size: 10px;
            color: #aaa;
            display: flex;
            justify-content: space-between;
        }

        /* Print button — hidden when printing */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1e3a5f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .print-btn:hover { background: #162d4a; }

        @media print {
            body { padding: 20px; }
            .print-btn { display: none; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body>

<button class="print-btn" onclick="window.print()">
    🖨 Print Report
</button>

{{-- Header --}}
<div class="report-header">
    <div>
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="company-sub">Construction Group</div>
    </div>
    <div class="report-meta">
        <strong>Financial Report</strong>
        Generated: {{ now()->format('F d, Y') }}
    </div>
</div>

{{-- Project info --}}
@if($project->number)
    <div style="font-size:11px;font-family:monospace;color:#888;margin-bottom:4px;">{{ $project->number }}</div>
@endif
<div class="project-title">{{ $project->name }}</div>
<div class="project-meta">
    <span><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $project->status)) }}</span>
    @if($project->address)
        <span><strong>Address:</strong> {{ $project->address }}</span>
    @endif
    @if($project->client)
        <span><strong>Client:</strong> {{ $project->client->name }}</span>
    @endif
    @if($project->start_date)
        <span><strong>Start:</strong> {{ $project->start_date->format('M d, Y') }}</span>
    @endif
    @if($project->estimated_completion_date)
        <span><strong>Est. Completion:</strong> {{ $project->estimated_completion_date->format('M d, Y') }}</span>
    @endif
</div>

{{-- Summary cards --}}
<div class="summary">
    <div class="summary-card income">
        <div class="summary-label">Total Income</div>
        <div class="summary-value">${{ number_format($totalIncome, 2) }}</div>
    </div>
    <div class="summary-card expense">
        <div class="summary-label">Total Expenses</div>
        <div class="summary-value">${{ number_format($totalExpenses, 2) }}</div>
    </div>
    <div class="summary-card net {{ $net >= 0 ? 'positive' : 'negative' }}">
        <div class="summary-label">Net Balance</div>
        <div class="summary-value">{{ $net >= 0 ? '+' : '-' }}${{ number_format(abs($net), 2) }}</div>
    </div>
    @if($project->budget)
        <div class="summary-card budget">
            <div class="summary-label">Budget</div>
            <div class="summary-value">${{ number_format($project->budget, 2) }}</div>
        </div>
    @endif
</div>

{{-- Budget progress --}}
@if($project->budget && $project->budget > 0)
    @php
        $pct      = min(100, round(($totalExpenses / $project->budget) * 100));
        $barClass = $pct >= 100 ? 'red' : ($pct >= 80 ? 'yellow' : 'green');
    @endphp
    <div class="budget-bar-wrap">
        <div class="budget-bar-label">
            <span>Budget Used: {{ $pct }}%</span>
            <span>Remaining: ${{ number_format(max(0, $project->budget - $totalExpenses), 2) }}</span>
        </div>
        <div class="budget-bar-bg">
            <div class="budget-bar-fill {{ $barClass }}" style="width: {{ $pct }}%"></div>
        </div>
    </div>
@endif

{{-- Income table --}}
@if($incomes->isNotEmpty())
    <div class="section-title">Income</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Source</th>
                <th>Payment</th>
                <th>Notes</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomes as $income)
                <tr>
                    <td style="white-space:nowrap">{{ $income->income_date->format('M d, Y') }}</td>
                    <td>{{ $income->description }}</td>
                    <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $income->source)) }}</span></td>
                    <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $income->payment_method)) }}</span></td>
                    <td style="color:#888">{{ $income->notes ?? '—' }}</td>
                    <td class="amount income-amount">${{ number_format($income->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">Total Income</td>
                <td class="amount income-amount">${{ number_format($totalIncome, 2) }}</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- Expenses table --}}
@if($expenses->isNotEmpty())
    <div class="section-title">Expenses</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Payment</th>
                <th>Notes</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td style="white-space:nowrap">{{ $expense->expense_date->format('M d, Y') }}</td>
                    <td>{{ $expense->description }}</td>
                    <td><span class="badge">{{ ucfirst($expense->category) }}</span></td>
                    <td><span class="badge">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</span></td>
                    <td style="color:#888">{{ $expense->notes ?? '—' }}</td>
                    <td class="amount expense-amount">${{ number_format($expense->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">Total Expenses</td>
                <td class="amount expense-amount">${{ number_format($totalExpenses, 2) }}</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- Internal notes --}}
@if($project->internal_notes)
    <div class="section-title">Internal Notes</div>
    <div class="notes-box">{{ $project->internal_notes }}</div>
@endif

{{-- Footer --}}
<div class="report-footer">
    <span>{{ $company->company_name }} — Confidential</span>
    <span>{{ $project->name }} · Generated {{ now()->format('M d, Y \a\t g:i A') }}</span>
</div>

</body>
</html>
