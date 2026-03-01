<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        h1 { color: #7c3aed; font-size: 22px; margin-bottom: 5px; }
        h2 { color: #5b21b6; font-size: 15px; margin-top: 20px; margin-bottom: 8px; border-bottom: 2px solid #ddd6fe; padding-bottom: 4px; }
        .kpi-grid { display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0 20px; }
        .kpi-card { background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 6px; padding: 10px 16px; min-width: 120px; text-align: center; }
        .kpi-card .num { font-size: 22px; font-weight: bold; color: #6d28d9; }
        .kpi-card .label { font-size: 11px; color: #7c3aed; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #5b21b6; color: white; padding: 7px 8px; text-align: left; font-size: 11px; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: top; }
        tr:nth-child(even) { background: #faf5ff; }
        .pass { color: #15803d; font-weight: bold; }
        .fail { color: #b91c1c; font-weight: bold; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>QuizMaster — Admin Report</h1>
    <p style="color:#6b7280; margin-top:0;">Generated on {{ now()->format('F d, Y H:i') }}</p>

    <h2>Summary KPIs</h2>
    <table>
        <tr><td><strong>Total Users</strong></td><td>{{ $summary['total_users'] }}</td></tr>
        <tr><td><strong>Active Users</strong></td><td>{{ $summary['active_users'] }}</td></tr>
        <tr><td><strong>Total Attempts</strong></td><td>{{ $summary['total_attempts'] }}</td></tr>
        <tr><td><strong>Average Score</strong></td><td>{{ $summary['avg_score'] }}</td></tr>
        <tr><td><strong>Pass Rate</strong></td><td>{{ $summary['pass_rate'] }}%</td></tr>
    </table>

    <div class="page-break"></div>

    <h2>Attempts</h2>
    <table>
        <thead>
            <tr>
                <th>#</th><th>User</th><th>Email</th><th>Attempt Code</th>
                <th>Score</th><th>Result</th><th>Finished At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attempts as $i => $a)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $a->user->name ?? 'N/A' }}</td>
                <td>{{ $a->user->email ?? 'N/A' }}</td>
                <td style="font-size:10px">{{ $a->attempt_code }}</td>
                <td>{{ $a->score }}/{{ $a->total_questions }}</td>
                <td class="{{ $a->score >= 50 ? 'pass' : 'fail' }}">{{ $a->score >= 50 ? 'PASS' : 'FAIL' }}</td>
                <td>{{ $a->finished_at?->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(count($questionAnalysis) > 0)
    <div class="page-break"></div>
    <h2>Question Analysis (Most Incorrect)</h2>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Question</th><th>Difficulty</th><th>Category</th>
                <th>Total</th><th>Correct</th><th>Incorrect</th><th>Rate%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($questionAnalysis as $i => $q)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ Str::limit($q->question_text, 60) }}</td>
                <td>{{ $q->difficulty }}</td>
                <td>{{ $q->category ?? 'N/A' }}</td>
                <td>{{ $q->total_attempts }}</td>
                <td>{{ $q->correct_count }}</td>
                <td>{{ $q->incorrect_count }}</td>
                <td>{{ $q->correct_rate }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</body>
</html>
