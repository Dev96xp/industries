<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Entries Report — {{ $company->company_name }}</title>
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
        .report-meta  { text-align: right; font-size: 11px; color: #666; }
        .report-meta strong { display: block; font-size: 14px; color: #1a1a1a; margin-bottom: 4px; }

        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            font-size: 12px;
            color: #555;
        }
        .filters-bar span { background: #f1f5f9; border-radius: 4px; padding: 4px 10px; }
        .filters-bar span strong { color: #1a1a1a; }

        .summary {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
        }
        .summary-card {
            flex: 1;
            border-radius: 8px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }
        .summary-card.hours { background: #eff6ff; border-color: #bfdbfe; }
        .summary-card.entries { background: #f0fdf4; border-color: #bbf7d0; }
        .summary-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #888; margin-bottom: 4px; }
        .summary-value { font-size: 22px; font-weight: 700; }
        .summary-card.hours .summary-value  { color: #2563eb; }
        .summary-card.entries .summary-value { color: #16a34a; }

        /* Section headers */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #1e3a5f;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Worker summary table */
        .worker-summary {
            margin-bottom: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        thead tr { background: #f1f5f9; }
        th {
            padding: 8px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        th.right, td.right { text-align: right; }
        td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #374151;
            font-size: 12px;
        }
        tr:last-child td { border-bottom: none; }
        tr.subtotal td {
            background: #f8fafc;
            font-weight: 600;
            color: #1a1a1a;
            border-top: 1px solid #e2e8f0;
        }
        .badge-active {
            display: inline-block;
            background: #dcfce7;
            color: #16a34a;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-verified {
            display: inline-block;
            background: #dbeafe;
            color: #2563eb;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 10px;
        }
        .badge-outside {
            display: inline-block;
            background: #fff7ed;
            color: #ea580c;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 10px;
        }

        .page-break { page-break-before: always; }

        .footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #aaa;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    {{-- Print button --}}
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="background:#1e3a5f;color:#fff;border:none;padding:8px 18px;border-radius:6px;font-size:13px;cursor:pointer;">
            🖨 Print / Save PDF
        </button>
        <button onclick="window.history.back()" style="background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;padding:8px 18px;border-radius:6px;font-size:13px;cursor:pointer;margin-left:8px;">
            ← Back
        </button>
    </div>

    {{-- Header --}}
    <div class="report-header">
        <div>
            <div class="company-name">{{ $company->company_name }}</div>
            <div class="company-sub">Construction Group</div>
        </div>
        <div class="report-meta">
            <strong>Time Entries Report</strong>
            Generated: {{ now()->format('M d, Y g:i A') }}
        </div>
    </div>

    {{-- Active filters --}}
    <div class="filters-bar">
        @if($worker)
            <span><strong>Worker:</strong> {{ $worker->name }}</span>
        @else
            <span><strong>Worker:</strong> All</span>
        @endif
        @if($project)
            <span><strong>Project:</strong> {{ $project->name }}</span>
        @else
            <span><strong>Project:</strong> All</span>
        @endif
        @if($dateFrom || $dateTo)
            <span><strong>Period:</strong>
                {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M d, Y') : '—' }}
                to
                {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : '—' }}
            </span>
        @else
            <span><strong>Period:</strong> All time</span>
        @endif
    </div>

    {{-- Summary --}}
    @php
        $totalMinutes = $entries->whereNotNull('clock_out_at')->sum(fn($e) => $e->duration_minutes);
        $hours = intdiv($totalMinutes, 60);
        $mins  = $totalMinutes % 60;
        $totalFormatted = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
    @endphp
    <div class="summary">
        <div class="summary-card hours">
            <div class="summary-label">Total Hours</div>
            <div class="summary-value">{{ $totalMinutes > 0 ? $totalFormatted : '0h' }}</div>
        </div>
        <div class="summary-card entries">
            <div class="summary-label">Total Entries</div>
            <div class="summary-value">{{ $entries->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Workers</div>
            <div class="summary-value">{{ $entries->pluck('user_id')->unique()->count() }}</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Projects</div>
            <div class="summary-value">{{ $entries->pluck('project_id')->unique()->count() }}</div>
        </div>
    </div>

    {{-- By Worker --}}
    <div class="section-title">Hours by Worker</div>
    <div class="worker-summary">
        <table>
            <thead>
                <tr>
                    <th>Worker</th>
                    <th class="right">Entries</th>
                    <th class="right">Total Hours</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries->groupBy('user_id') as $userId => $workerEntries)
                    @php
                        $wMinutes = $workerEntries->whereNotNull('clock_out_at')->sum(fn($e) => $e->duration_minutes);
                        $wH = intdiv($wMinutes, 60);
                        $wM = $wMinutes % 60;
                    @endphp
                    <tr>
                        <td>{{ $workerEntries->first()->worker->name }}</td>
                        <td class="right">{{ $workerEntries->count() }}</td>
                        <td class="right">{{ $wH > 0 ? "{$wH}h {$wM}m" : "{$wM}m" }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- By Project --}}
    <div class="section-title">Hours by Project</div>
    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th class="right">Entries</th>
                <th class="right">Total Hours</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries->groupBy('project_id') as $projectId => $projectEntries)
                @php
                    $pMinutes = $projectEntries->whereNotNull('clock_out_at')->sum(fn($e) => $e->duration_minutes);
                    $pH = intdiv($pMinutes, 60);
                    $pM = $pMinutes % 60;
                @endphp
                <tr>
                    <td>{{ $projectEntries->first()->project->name }}</td>
                    <td class="right">{{ $projectEntries->count() }}</td>
                    <td class="right">{{ $pH > 0 ? "{$pH}h {$pM}m" : "{$pM}m" }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Detailed entries --}}
    <div class="section-title" style="margin-top: 8px;">Detailed Entries</div>
    <table>
        <thead>
            <tr>
                <th>Worker</th>
                <th>Project</th>
                <th>Clock In</th>
                <th>Clock Out</th>
                <th class="right">Duration</th>
                <th class="right">Location</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td>{{ $entry->worker->name }}</td>
                    <td>{{ $entry->project->name }}</td>
                    <td>
                        {{ $entry->clock_in_at->format('M d, Y') }}<br>
                        <span style="color:#888;font-size:11px;">{{ $entry->clock_in_at->format('g:i A') }}</span>
                    </td>
                    <td>
                        @if($entry->clock_out_at)
                            {{ $entry->clock_out_at->format('M d, Y') }}<br>
                            <span style="color:#888;font-size:11px;">{{ $entry->clock_out_at->format('g:i A') }}</span>
                        @else
                            <span class="badge-active">Active</span>
                        @endif
                    </td>
                    <td class="right">{{ $entry->formatted_duration }}</td>
                    <td class="right">
                        @if(! $entry->clock_in_lat)
                            —
                        @elseif($entry->clock_in_verified && ($entry->clock_out_at === null || $entry->clock_out_verified))
                            <span class="badge-verified">Verified</span>
                        @else
                            <span class="badge-outside">⚠ Outside</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <span>{{ $company->company_name }} — Confidential</span>
        <span>Generated {{ now()->format('M d, Y g:i A') }}</span>
    </div>

</body>
</html>
