<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSummary(array $filters): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $category = $filters['category'] ?? null;
        $difficulty = $filters['difficulty'] ?? null;
        $className = $filters['class_name'] ?? null;
        $generation = $filters['generation'] ?? null;

        $attemptsQuery = QuizAttempt::query()->where('quiz_attempts.status', 'completed');
        $answersQuery = QuizAnswer::query()
            ->join('quiz_attempts', 'quiz_answers.quiz_attempt_id', '=', 'quiz_attempts.id')
            ->join('questions', 'quiz_answers.question_id', '=', 'questions.id')
            ->where('quiz_attempts.status', 'completed');

        $usersQuery = User::query();

        if ($className || $generation) {
            $attemptsQuery->join('users', 'quiz_attempts.user_id', '=', 'users.id');
            $answersQuery->join('users', 'quiz_attempts.user_id', '=', 'users.id');
        }

        if ($from) {
            $attemptsQuery->whereDate('quiz_attempts.finished_at', '>=', $from);
            $answersQuery->whereDate('quiz_attempts.finished_at', '>=', $from);
        }
        if ($to) {
            $attemptsQuery->whereDate('quiz_attempts.finished_at', '<=', $to);
            $answersQuery->whereDate('quiz_attempts.finished_at', '<=', $to);
        }
        if ($category) {
            $answersQuery->where('questions.category', $category);
        }
        if ($difficulty) {
            $answersQuery->where('questions.difficulty', $difficulty);
        }
        if ($className) {
            $attemptsQuery->where('users.class_name', $className);
            $usersQuery->where('class_name', $className);
        }
        if ($generation) {
            $attemptsQuery->where('users.generation', $generation);
            $usersQuery->where('generation', $generation);
        }

        $totalUsers = $usersQuery->count();
        $activeUsers = (clone $usersQuery)->where('is_active', true)->count();
        $totalAttempts = $attemptsQuery->count();
        $avgScore = round($attemptsQuery->avg('quiz_attempts.score') ?? 0, 2);
        $passCount = (clone $attemptsQuery)->where('quiz_attempts.score', '>=', 50)->count();
        $passRate = $totalAttempts > 0 ? round(($passCount / $totalAttempts) * 100, 2) : 0;

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_attempts' => $totalAttempts,
            'avg_score' => $avgScore,
            'pass_rate' => $passRate,
            'pass_count' => $passCount,
        ];
    }

    public function getAttempts(array $filters)
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        $query = QuizAttempt::with(['user', 'subject'])
            ->whereHas('user')
            ->where('quiz_attempts.status', 'completed')
            ->orderBy('quiz_attempts.started_at', 'desc');

        if (!empty($filters['class_name']) || !empty($filters['generation'])) {
            $query->join('users', 'quiz_attempts.user_id', '=', 'users.id');
        }

        if (!empty($filters['from'])) {
            $query->whereDate('quiz_attempts.finished_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('quiz_attempts.finished_at', '<=', $filters['to']);
        }
        if (isset($filters['min_score']) && $filters['min_score'] !== '') {
            $query->where('quiz_attempts.score', '>=', $filters['min_score']);
        }
        if (isset($filters['max_score']) && $filters['max_score'] !== '') {
            $query->where('quiz_attempts.score', '<=', $filters['max_score']);
        }
        if (!empty($filters['class_name'])) {
            $query->where('users.class_name', $filters['class_name']);
        }
        if (!empty($filters['generation'])) {
            $query->where('users.generation', $filters['generation']);
        }

        return $query->select('quiz_attempts.*')->paginate($perPage);
    }

    public function getQuestionAnalysis(array $filters): array
    {
        $query = DB::table('quiz_answers')
            ->join('questions', 'quiz_answers.question_id', '=', 'questions.id')
            ->join('quiz_attempts', 'quiz_answers.quiz_attempt_id', '=', 'quiz_attempts.id')
            ->where('quiz_attempts.status', 'completed')
            ->where('quiz_answers.is_locked', true)
            ->select(
                'questions.id',
                'questions.question_text',
                'questions.difficulty',
                'questions.category',
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('SUM(CASE WHEN quiz_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
                DB::raw('SUM(CASE WHEN quiz_answers.is_correct = 0 THEN 1 ELSE 0 END) as incorrect_count'),
                DB::raw('ROUND(SUM(CASE WHEN quiz_answers.is_correct = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as correct_rate')
            );

        if (!empty($filters['from'])) {
            $query->whereDate('quiz_attempts.finished_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('quiz_attempts.finished_at', '<=', $filters['to']);
        }

        return $query
            ->groupBy('questions.id', 'questions.question_text', 'questions.difficulty', 'questions.category')
            ->orderBy('incorrect_count', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function getAttemptReportData(QuizAttempt $attempt): array
    {
        $answers = $attempt->answers()->with('question')->orderBy('id')->get();

        $locked = $answers->where('is_locked', true);
        $correct = $locked->where('is_correct', true)->count();
        $incorrect = $locked->where('is_correct', false)->whereNotNull('selected_choice')->count();
        $unanswered = $locked->whereNull('selected_choice')->count();

        return [
            'user' => $attempt->user,
            'attempt' => $attempt,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'unanswered' => $unanswered,
            'avg_time' => round($locked->avg('time_taken_seconds') ?? 0, 2),
            'answers' => $answers,
        ];
    }
}
