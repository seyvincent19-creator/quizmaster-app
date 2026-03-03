<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AdminReportExport;
use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function summary(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to', 'category', 'difficulty', 'class_name', 'generation']);
        $data = $this->reportService->getSummary($filters);
        return response()->json($data);
    }

    public function attempts(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to', 'min_score', 'max_score', 'class_name', 'generation', 'per_page']);
        $attempts = $this->reportService->getAttempts($filters);

        return response()->json([
            'data' => $attempts->map(fn($a) => [
                'id' => $a->id,
                'attempt_code' => $a->attempt_code,
                'user' => [
                    'id' => $a->user?->id,
                    'name' => $a->user?->name ?? 'Unknown User',
                    'email' => $a->user?->email,
                    'class_name' => $a->user?->class_name,
                    'generation' => $a->user?->generation,
                ],
                'subject_name' => $a->subject?->name ?? 'All Subjects',
                'score' => $a->score,
                'total_questions' => $a->total_questions,
                'status' => $a->status,
                'started_at' => $a->started_at?->toISOString(),
                'finished_at' => $a->finished_at?->toISOString(),
            ])->values(),
            'meta' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'total' => $attempts->total(),
                'per_page' => $attempts->perPage(),
            ],
        ]);
    }

    public function questionAnalysis(Request $request): JsonResponse
    {
        $filters = $request->only(['from', 'to']);
        $data = $this->reportService->getQuestionAnalysis($filters);
        return response()->json(['data' => $data]);
    }

    public function byClass(): JsonResponse
    {
        $data = QuizAttempt::query()
            ->join('users', 'quiz_attempts.user_id', '=', 'users.id')
            ->whereNotNull('users.class_name')
            ->where('users.class_name', '!=', '')
            ->where('quiz_attempts.status', 'completed')
            ->groupBy('users.class_name')
            ->selectRaw('users.class_name, COUNT(quiz_attempts.id) as total_attempts, ROUND(AVG(quiz_attempts.score * 100.0 / quiz_attempts.total_questions), 1) as avg_score, ROUND(SUM(CASE WHEN quiz_attempts.score >= quiz_attempts.total_questions * 0.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(quiz_attempts.id), 1) as pass_rate')
            ->orderBy('users.class_name')
            ->get();

        return response()->json($data);
    }

    public function byGeneration(): JsonResponse
    {
        $data = QuizAttempt::query()
            ->join('users', 'quiz_attempts.user_id', '=', 'users.id')
            ->whereNotNull('users.generation')
            ->where('users.generation', '!=', '')
            ->where('quiz_attempts.status', 'completed')
            ->groupBy('users.generation')
            ->selectRaw('users.generation, COUNT(quiz_attempts.id) as total_attempts, ROUND(AVG(quiz_attempts.score * 100.0 / quiz_attempts.total_questions), 1) as avg_score, ROUND(SUM(CASE WHEN quiz_attempts.score >= quiz_attempts.total_questions * 0.5 THEN 1 ELSE 0 END) * 100.0 / COUNT(quiz_attempts.id), 1) as pass_rate')
            ->orderBy('users.generation')
            ->get();

        return response()->json($data);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['from', 'to', 'min_score', 'max_score', 'category', 'difficulty']);

        $summary = $this->reportService->getSummary($filters);
        $attempts = $this->reportService->getAttempts($filters)->getCollection();
        $questionAnalysis = $this->reportService->getQuestionAnalysis($filters);

        return Excel::download(
            new AdminReportExport($summary, $attempts, $questionAnalysis),
            'admin-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['from', 'to', 'min_score', 'max_score', 'category', 'difficulty']);

        $summary = $this->reportService->getSummary($filters);
        $attempts = $this->reportService->getAttempts($filters)->getCollection();
        $questionAnalysis = $this->reportService->getQuestionAnalysis($filters);

        $pdf = Pdf::loadView('pdf.admin_report', compact('summary', 'attempts', 'questionAnalysis'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('admin-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
