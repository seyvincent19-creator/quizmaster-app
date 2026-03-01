<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class AdminReportExport implements WithMultipleSheets
{
    public function __construct(
        protected array $summary,
        protected Collection $attempts,
        protected array $questionAnalysis
    ) {}

    public function sheets(): array
    {
        return [
            new AdminSummarySheet($this->summary),
            new AdminAttemptsSheet($this->attempts),
            new AdminQuestionAnalysisSheet($this->questionAnalysis),
        ];
    }
}

class AdminSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(protected array $summary) {}

    public function title(): string { return 'Summary KPIs'; }

    public function headings(): array { return ['Metric', 'Value']; }

    public function collection()
    {
        return collect([
            ['Total Users', $this->summary['total_users']],
            ['Active Users', $this->summary['active_users']],
            ['Total Attempts', $this->summary['total_attempts']],
            ['Average Score', $this->summary['avg_score']],
            ['Pass Rate (%)', $this->summary['pass_rate']],
            ['Pass Count', $this->summary['pass_count']],
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

class AdminAttemptsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(protected Collection $attempts) {}

    public function title(): string { return 'Attempts'; }

    public function headings(): array
    {
        return ['#', 'User', 'Email', 'Attempt Code', 'Score', 'Pass/Fail', 'Started At', 'Finished At'];
    }

    public function collection()
    {
        return $this->attempts->map(fn($a, $i) => [
            $i + 1,
            $a->user->name ?? 'N/A',
            $a->user->email ?? 'N/A',
            $a->attempt_code,
            $a->score . ' / ' . $a->total_questions,
            $a->score >= 50 ? 'PASS' : 'FAIL',
            $a->started_at?->format('Y-m-d H:i:s'),
            $a->finished_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

class AdminQuestionAnalysisSheet implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    public function __construct(protected array $questions) {}

    public function title(): string { return 'Question Analysis'; }

    public function headings(): array
    {
        return ['#', 'Question', 'Difficulty', 'Category', 'Total Attempts', 'Correct', 'Incorrect', 'Correct Rate (%)'];
    }

    public function collection()
    {
        return collect($this->questions)->map(fn($q, $i) => [
            $i + 1,
            $q->question_text,
            $q->difficulty,
            $q->category ?? 'N/A',
            $q->total_attempts,
            $q->correct_count,
            $q->incorrect_count,
            $q->correct_rate,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
