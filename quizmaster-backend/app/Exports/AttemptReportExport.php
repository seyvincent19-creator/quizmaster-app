<?php

namespace App\Exports;

use App\Models\QuizAttempt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttemptReportExport implements WithMultipleSheets
{
    public function __construct(protected QuizAttempt $attempt) {}

    public function sheets(): array
    {
        return [
            'Summary' => new AttemptSummarySheet($this->attempt),
            'Answers' => new AttemptAnswersSheet($this->attempt),
        ];
    }
}

class AttemptSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(protected QuizAttempt $attempt) {}

    public function title(): string { return 'Summary'; }

    public function headings(): array
    {
        return ['Field', 'Value'];
    }

    public function collection()
    {
        $attempt = $this->attempt;
        $user = $attempt->user;
        $answers = $attempt->answers()->where('is_locked', true);
        $correct = (clone $answers)->where('is_correct', true)->count();
        $incorrect = (clone $answers)->where('is_correct', false)->whereNotNull('selected_choice')->count();
        $unanswered = (clone $answers)->whereNull('selected_choice')->count();
        $avgTime = round((clone $answers)->avg('time_taken_seconds') ?? 0, 2);

        return collect([
            ['User Name', $user->name],
            ['User Email', $user->email],
            ['Attempt Code', $attempt->attempt_code],
            ['Started At', $attempt->started_at?->format('Y-m-d H:i:s')],
            ['Finished At', $attempt->finished_at?->format('Y-m-d H:i:s')],
            ['Score', $attempt->score . ' / ' . $attempt->total_questions],
            ['Correct Answers', $correct],
            ['Incorrect Answers', $incorrect],
            ['Unanswered', $unanswered],
            ['Avg Time per Question (s)', $avgTime],
            ['Pass/Fail', $attempt->score >= 50 ? 'PASS' : 'FAIL'],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class AttemptAnswersSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(protected QuizAttempt $attempt) {}

    public function title(): string { return 'Answers'; }

    public function headings(): array
    {
        return ['#', 'Question', 'Your Answer', 'Correct Answer', 'Result', 'Time (s)', 'Explanation'];
    }

    public function collection()
    {
        return $this->attempt->answers()
            ->with('question')
            ->orderBy('id')
            ->get()
            ->map(fn($a, $i) => [
                $i + 1,
                $a->question->question_text,
                $a->selected_choice ?? 'No Answer',
                $a->question->correct_choice,
                $a->is_correct ? 'Correct' : ($a->selected_choice ? 'Incorrect' : 'Unanswered'),
                $a->time_taken_seconds ?? 0,
                $a->question->explanation ?? '',
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
