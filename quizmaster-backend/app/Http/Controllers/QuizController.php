<?php

namespace App\Http\Controllers;

use App\Exports\AttemptReportExport;
use App\Http\Requests\Quiz\AnswerRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QuizController extends Controller
{
    public function __construct(
        protected QuizService $quizService,
        protected ReportService $reportService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ]);

        $actor = $this->getActor();
        $attempt = $this->quizService->startQuiz(
            $actor,
            $request->input('subject_id'),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($this->formatAttemptForQuiz($attempt));
    }

    public function resume(Request $request, string $attemptCode): JsonResponse
    {
        $actor = $this->getActor();
        $attempt = $this->quizService->getAttempt($actor, $attemptCode);

        return response()->json($this->formatAttemptForQuiz($attempt));
    }

    public function answer(AnswerRequest $request, string $attemptCode): JsonResponse
    {
        $actor = $this->getActor();
        $attempt = $this->quizService->getAttempt($actor, $attemptCode);

        if ($attempt->status === 'completed') {
            return response()->json(['message' => 'This quiz is already completed.'], 422);
        }

        $answer = $this->quizService->saveAnswer(
            $attempt,
            $request->question_id,
            $request->selected_choice,
            $request->time_taken_seconds,
            $request->boolean('is_locked', false)
        );

        return response()->json([
            'question_id' => $answer->question_id,
            'selected_choice' => $answer->selected_choice,
            'is_locked' => $answer->is_locked,
            'current_index' => $attempt->fresh()->current_index,
        ]);
    }

    public function finish(Request $request, string $attemptCode): JsonResponse
    {
        $actor = $this->getActor();
        $attempt = $this->quizService->getAttempt($actor, $attemptCode);
        $attempt = $this->quizService->finishQuiz($attempt);

        return response()->json([
            'message' => 'Quiz completed successfully.',
            'attempt_code' => $attempt->attempt_code,
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
        ]);
    }

    public function result(Request $request, string $attemptCode): JsonResponse
    {
        $actor = $this->getActor();
        $attempt = $this->quizService->getAttempt($actor, $attemptCode);

        if ($attempt->status !== 'completed') {
            return response()->json(['message' => 'Quiz is not yet completed.'], 422);
        }

        $result = $this->quizService->getResult($attempt);
        return response()->json($result);
    }

    public function history(Request $request): JsonResponse
    {
        $actor = $this->getActor();
        $history = $this->quizService->getHistory($actor);

        return response()->json([
            'data' => QuizAttemptResource::collection($history->items()),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'total' => $history->total(),
                'per_page' => $history->perPage(),
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $actor = $this->getActor();
        $fkField = $actor instanceof \App\Models\Admin ? 'admin_id' : 'user_id';

        $attempts = QuizAttempt::where($fkField, $actor->id)
            ->where('status', 'completed')
            ->with(['subject', 'answers'])
            ->orderBy('finished_at', 'desc')
            ->get();

        $total = $attempts->count();
        $passed = $attempts->filter(fn($a) => $a->score >= 50)->count();

        $bySubject = $attempts
            ->groupBy(fn($a) => $a->subject?->name ?? 'All Subjects')
            ->map(fn($group, $name) => [
                'subject' => $name,
                'attempts' => $group->count(),
                'avg_score' => round($group->avg('score'), 1),
                'best_score' => $group->max('score'),
                'pass_rate' => round($group->filter(fn($a) => $a->score >= 50)->count() / $group->count() * 100),
            ])
            ->values();

        $recentScores = $attempts->take(10)->map(fn($a) => [
            'attempt_code' => $a->attempt_code,
            'score' => $a->score,
            'subject' => $a->subject?->name ?? 'All Subjects',
            'date' => $a->finished_at?->format('M d'),
        ])->values()->reverse()->values();

        $totalCorrect = 0;
        $totalAnswered = 0;
        foreach ($attempts as $a) {
            $locked = $a->answers->where('is_locked', true);
            $totalCorrect += $locked->where('is_correct', true)->count();
            $totalAnswered += $locked->count();
        }

        return response()->json([
            'total_attempts' => $total,
            'passed' => $passed,
            'failed' => $total - $passed,
            'pass_rate' => $total > 0 ? round($passed / $total * 100) : 0,
            'avg_score' => $total > 0 ? round($attempts->avg('score'), 1) : 0,
            'best_score' => $total > 0 ? $attempts->max('score') : 0,
            'total_correct' => $totalCorrect,
            'total_answered' => $totalAnswered,
            'by_subject' => $bySubject,
            'recent_scores' => $recentScores,
        ]);
    }

    public function downloadPdf(Request $request, string $attemptCode)
    {
        $actor = $this->getActor();
        $fkField = $actor instanceof \App\Models\Admin ? 'admin_id' : 'user_id';
        $attempt = QuizAttempt::where($fkField, $actor->id)
            ->where('attempt_code', $attemptCode)
            ->where('status', 'completed')
            ->firstOrFail();

        $data = $this->reportService->getAttemptReportData($attempt);

        $pdf = Pdf::loadView('pdf.attempt_report', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download("quiz-report-{$attemptCode}.pdf");
    }

    public function downloadExcel(Request $request, string $attemptCode): BinaryFileResponse
    {
        $actor = $this->getActor();
        $fkField = $actor instanceof \App\Models\Admin ? 'admin_id' : 'user_id';
        $attempt = QuizAttempt::where($fkField, $actor->id)
            ->where('attempt_code', $attemptCode)
            ->where('status', 'completed')
            ->firstOrFail();

        return Excel::download(
            new AttemptReportExport($attempt),
            "quiz-report-{$attemptCode}.xlsx"
        );
    }

    private function getActor()
    {
        // auth('admin') is set by AdminAuthenticate middleware when admin token is used.
        // auth('sanctum') is set by auth:sanctum middleware when user token is used.
        // Since Sanctum is polymorphic, auth('sanctum')->user() can also return Admin
        // if the admin token is used on a sanctum-guarded route.
        return auth('admin')->user() ?? auth('sanctum')->user();
    }

    private function formatAttemptForQuiz($attempt): array
    {
        $answersMap = $attempt->answers->keyBy('question_id');

        // Questions WITHOUT correct_choice to prevent cheating; choices shuffled per-user
        $questions = $attempt->answers->map(function ($a) {
            $q = $a->question;
            $origChoices = ['A' => $q->choice_a, 'B' => $q->choice_b, 'C' => $q->choice_c, 'D' => $q->choice_d];
            $order = $a->choice_order ?? ['A', 'B', 'C', 'D'];
            return [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'choice_a' => $origChoices[$order[0]],
                'choice_b' => $origChoices[$order[1]],
                'choice_c' => $origChoices[$order[2]],
                'choice_d' => $origChoices[$order[3]],
                'difficulty' => $q->difficulty,
                'category' => $q->category,
            ];
        })->values();

        // Current answer states — include choice_order so frontend can map selected original choice to displayed position
        $answers = $attempt->answers->map(fn($a) => [
            'question_id' => $a->question_id,
            'selected_choice' => $a->selected_choice,
            'is_locked' => $a->is_locked,
            'time_taken_seconds' => $a->time_taken_seconds,
            'choice_order' => $a->choice_order ?? ['A', 'B', 'C', 'D'],
        ])->keyBy('question_id');

        return [
            'attempt_code' => $attempt->attempt_code,
            'status' => $attempt->status,
            'current_index' => $attempt->current_index,
            'total_questions' => $attempt->total_questions,
            'subject_id' => $attempt->subject_id,
            'subject_name' => $attempt->subject?->name,
            'started_at' => $attempt->started_at?->toISOString(),
            'questions' => $questions,
            'answers' => $answers,
        ];
    }
}
