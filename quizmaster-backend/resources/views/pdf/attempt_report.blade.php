<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quiz Report - {{ $attempt->attempt_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        h1 { color: #2563eb; font-size: 22px; margin-bottom: 5px; }
        h2 { color: #1e40af; font-size: 16px; margin-top: 20px; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-table td { padding: 6px 10px; border: 1px solid #e5e7eb; }
        .info-table td:first-child { font-weight: bold; background: #f8fafc; width: 35%; }
        .summary-box { display: flex; gap: 10px; margin: 10px 0; }
        .score-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 16px; text-align: center; flex: 1; }
        .score-card .num { font-size: 28px; font-weight: bold; color: #1d4ed8; }
        .score-card .label { font-size: 11px; color: #6b7280; }
        .answers-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .answers-table th { background: #1e40af; color: white; padding: 7px 8px; text-align: left; font-size: 11px; }
        .answers-table td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: top; }
        .answers-table tr:nth-child(even) { background: #f8fafc; }
        .correct { color: #16a34a; font-weight: bold; }
        .incorrect { color: #dc2626; font-weight: bold; }
        .unanswered { color: #9ca3af; font-style: italic; }
        .pass { background: #dcfce7; color: #15803d; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
        .fail { background: #fee2e2; color: #b91c1c; padding: 4px 12px; border-radius: 4px; font-weight: bold; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <h1>QuizMaster — Attempt Report</h1>
    <p style="color:#6b7280; margin-top:0;">Generated on {{ now()->format('F d, Y H:i') }}</p>

    <h2>Student Information</h2>
    <table class="info-table">
        <tr><td>Name</td><td>{{ $user->name }}</td></tr>
        <tr><td>Email</td><td>{{ $user->email }}</td></tr>
        <tr><td>Attempt Code</td><td>{{ $attempt->attempt_code }}</td></tr>
        <tr><td>Started At</td><td>{{ $attempt->started_at?->format('Y-m-d H:i:s') }}</td></tr>
        <tr><td>Finished At</td><td>{{ $attempt->finished_at?->format('Y-m-d H:i:s') }}</td></tr>
        <tr>
            <td>Status</td>
            <td>
                @if($attempt->score >= 50)
                    <span class="pass">PASS</span>
                @else
                    <span class="fail">FAIL</span>
                @endif
            </td>
        </tr>
    </table>

    <h2>Score Summary</h2>
    <table class="info-table">
        <tr><td>Total Score</td><td>{{ $attempt->score }} / {{ $attempt->total_questions }}</td></tr>
        <tr><td>Correct Answers</td><td>{{ $correct }}</td></tr>
        <tr><td>Incorrect Answers</td><td>{{ $incorrect }}</td></tr>
        <tr><td>Unanswered</td><td>{{ $unanswered }}</td></tr>
        <tr><td>Avg Time per Question</td><td>{{ $avg_time }} seconds</td></tr>
    </table>

    <div class="page-break"></div>

    <h2>Answer Review</h2>
    <table class="answers-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:35%">Question</th>
                <th style="width:10%">Your Answer</th>
                <th style="width:10%">Correct</th>
                <th style="width:8%">Result</th>
                <th style="width:8%">Time (s)</th>
                <th style="width:25%">Explanation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($answers as $i => $answer)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $answer->question->question_text }}</td>
                <td>{{ $answer->selected_choice ?? '—' }}</td>
                <td>{{ $answer->question->correct_choice }}</td>
                <td>
                    @if($answer->is_correct)
                        <span class="correct">✓</span>
                    @elseif(!$answer->selected_choice)
                        <span class="unanswered">—</span>
                    @else
                        <span class="incorrect">✗</span>
                    @endif
                </td>
                <td>{{ $answer->time_taken_seconds ?? 0 }}</td>
                <td>{{ $answer->question->explanation ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
