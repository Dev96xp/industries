<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Report — {{ $task->name }}</title>
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
        .report-meta strong { display: block; font-size: 13px; color: #1a1a1a; }

        .project-ref  { font-size: 11px; color: #888; font-family: monospace; margin-bottom: 4px; }
        .task-title   { font-size: 20px; font-weight: 700; color: #1a1a1a; margin-bottom: 8px; }

        .meta-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .meta-item { font-size: 12px; color: #555; }
        .meta-item strong { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #888; margin-bottom: 2px; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-pending     { background: #f1f5f9; color: #64748b; }
        .badge-in_progress { background: #dbeafe; color: #1d4ed8; }
        .badge-completed   { background: #dcfce7; color: #15803d; }
        .badge-delayed     { background: #fef9c3; color: #a16207; }
        .badge-cancelled   { background: #fee2e2; color: #dc2626; }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1e3a5f;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 12px;
            margin-top: 24px;
        }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        td { padding: 9px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .notes-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            margin-top: 24px;
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }

        .report-footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            margin-top: 32px;
            font-size: 10px;
            color: #aaa;
            display: flex;
            justify-content: space-between;
        }

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

<button class="print-btn" onclick="window.print()">🖨 Print</button>

{{-- Header --}}
<div class="report-header">
    <div>
        <div class="company-name">{{ $company->company_name }}</div>
        <div class="company-sub">Construction Group</div>
    </div>
    <div class="report-meta">
        <strong>Task Report</strong>
        Generated: {{ now()->format('F d, Y') }}
    </div>
</div>

{{-- Task info --}}
<div class="project-ref">{{ $project->name }}@if($project->number) · {{ $project->number }}@endif</div>
<div class="task-title">{{ $task->name }}</div>

<div class="meta-grid">
    <div class="meta-item">
        <strong>Status</strong>
        <span class="badge badge-{{ $task->status }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
    </div>
    <div class="meta-item">
        <strong>Start Date</strong>
        {{ $task->start_date->format('M d, Y') }}
    </div>
    <div class="meta-item">
        <strong>End Date</strong>
        {{ $task->end_date->format('M d, Y') }}
    </div>
    <div class="meta-item">
        <strong>Assigned To</strong>
        {{ $task->assignedLabel() }}
        @if($task->isExternal()) <span style="font-size:10px;color:#888">(External)</span> @endif
    </div>
    @if($task->description)
        <div class="meta-item" style="flex-basis:100%">
            <strong>Description</strong>
            {{ $task->description }}
        </div>
    @endif
</div>

{{-- Subtasks --}}
@if($task->subtasks->isNotEmpty())
    <div class="section-title">Subtasks</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Status</th>
                <th>Assigned To</th>
            </tr>
        </thead>
        <tbody>
            @foreach($task->subtasks as $index => $subtask)
                <tr>
                    <td style="color:#aaa;width:32px">{{ $index + 1 }}</td>
                    <td>{{ $subtask->name }}</td>
                    <td><span class="badge badge-{{ $subtask->status }}">{{ ucfirst(str_replace('_', ' ', $subtask->status)) }}</span></td>
                    <td>{{ $subtask->assignedUser?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Notes --}}
@if($task->notes)
    <div class="section-title">Notes</div>
    <div class="notes-box">{{ $task->notes }}</div>
@endif

{{-- Footer --}}
<div class="report-footer">
    <span>{{ $company->company_name }} — Confidential</span>
    <span>{{ $task->name }} · Generated {{ now()->format('M d, Y \a\t g:i A') }}</span>
</div>

</body>
</html>
