<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Question;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QuizService
{
    public function startQuiz(User|Admin $actor, ?int $subjectId, string $ipAddress = null, string $userAgent = null): QuizAttempt
    {
        $isAdmin = $actor instanceof Admin;
        $fkField = $isAdmin ? 'admin_id' : 'user_id';

        // Check for active in-progress attempt
        $existing = QuizAttempt::where($fkField, $actor->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            return $existing->load('answers.question', 'subject');
        }

        if ($subjectId !== null) {
            Subject::active()->findOrFail($subjectId);
            $questionCount = Question::active()->where('subject_id', $subjectId)->count();
            if ($questionCount < 1) {
                throw new HttpException(422, "No active questions available for this subject.");
            }
        } else {
            $activeCount = Question::active()->count();
            if ($activeCount < 100) {
                throw new HttpException(422, "Not enough active questions. Required: 100, Available: {$activeCount}");
            }
        }

        return DB::transaction(function () use ($actor, $fkField, $subjectId, $ipAddress, $userAgent) {
            $questions = $subjectId !== null
                ? Question::active()->where('subject_id', $subjectId)->inRandomOrder()->get()
                : Question::active()->inRandomOrder()->limit(100)->get();

            $attempt = QuizAttempt::create([
                $fkField => $actor->id,
                'subject_id' => $subjectId,
                'attempt_code' => Str::uuid()->toString(),
                'started_at' => now(),
                'total_questions' => $questions->count(),
                'status' => 'in_progress',
                'current_index' => 0,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            $choices = ['A', 'B', 'C', 'D'];
            $answers = $questions->map(function ($q) use ($attempt, $choices) {
                $order = $choices;
                shuffle($order);
                return [
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $q->id,
                    'choice_order' => json_encode($order),
                    'is_locked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            QuizAnswer::insert($answers);

            return $attempt->fresh(['answers.question', 'subject']);
        });
    }

    public function getAttempt(User|Admin $actor, string $attemptCode): QuizAttempt
    {
        $fkField = $actor instanceof Admin ? 'admin_id' : 'user_id';

        return QuizAttempt::where($fkField, $actor->id)
            ->where('attempt_code', $attemptCode)
            ->with('answers.question', 'subject')
            ->firstOrFail();
    }

    public function saveAnswer(QuizAttempt $attempt, int $questionId, ?string $selectedChoice, ?int $timeTaken, bool $isLocked): QuizAnswer
    {
        $answer = $attempt->answers()
            ->where('question_id', $questionId)
            ->firstOrFail();

        if ($answer->is_locked) {
            throw new HttpException(422, 'This answer is already locked and cannot be changed.');
        }

        if ($attempt->status === 'completed') {
            throw new HttpException(422, 'This quiz attempt is already completed.');
        }

        // Convert displayed choice position to the original choice letter
        if ($selectedChoice !== null && $answer->choice_order) {
            $posMap = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
            $selectedChoice = $answer->choice_order[$posMap[$selectedChoice]];
        }

        $isCorrect = null;
        if ($isLocked && $selectedChoice !== null) {
            $isCorrect = $answer->question->correct_choice === $selectedChoice;
        } elseif ($isLocked && $selectedChoice === null) {
            $isCorrect = false;
        }

        if ($isLocked) {
            $allAnswers = $attempt->answers()->orderBy('id')->pluck('question_id')->toArray();
            $pos = array_search($questionId, $allAnswers);
            if ($pos !== false && $pos + 1 > $attempt->current_index) {
                $attempt->update(['current_index' => $pos + 1]);
            }
        }

        $answer->update([
            'selected_choice' => $selectedChoice,
            'is_correct' => $isCorrect,
            'answered_at' => now(),
            'time_taken_seconds' => $timeTaken,
            'is_locked' => $isLocked,
        ]);

        return $answer->fresh('question');
    }

    public function finishQuiz(QuizAttempt $attempt): QuizAttempt
    {
        if ($attempt->status === 'completed') {
            throw new HttpException(422, 'Quiz is already completed.');
        }

        return DB::transaction(function () use ($attempt) {
            $attempt->answers()
                ->where('is_locked', false)
                ->each(function ($answer) {
                    $isCorrect = ($answer->selected_choice !== null)
                        ? ($answer->question->correct_choice === $answer->selected_choice)
                        : false;

                    $answer->update([
                        'is_locked' => true,
                        'is_correct' => $isCorrect,
                    ]);
                });

            $score = $attempt->answers()->where('is_correct', true)->count();

            $attempt->update([
                'status' => 'completed',
                'finished_at' => now(),
                'score' => $score,
                'current_index' => $attempt->total_questions,
            ]);

            return $attempt->fresh('answers.question');
        });
    }

    public function getResult(QuizAttempt $attempt): array
    {
        $answers = $attempt->answers()
            ->with('question')
            ->orderBy('id')
            ->get();

        $answerData = $answers->map(fn($a) => [
            'question_id' => $a->question_id,
            'question_text' => $a->question->question_text,
            'choice_a' => $a->question->choice_a,
            'choice_b' => $a->question->choice_b,
            'choice_c' => $a->question->choice_c,
            'choice_d' => $a->question->choice_d,
            'correct_choice' => $a->question->correct_choice,
            'explanation' => $a->question->explanation,
            'selected_choice' => $a->selected_choice,
            'is_correct' => $a->is_correct,
            'time_taken_seconds' => $a->time_taken_seconds,
            'is_locked' => $a->is_locked,
        ]);

        $locked = $answers->where('is_locked', true);

        return [
            'attempt' => [
                'id' => $attempt->id,
                'attempt_code' => $attempt->attempt_code,
                'status' => $attempt->status,
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'subject_name' => $attempt->subject?->name,
                'started_at' => $attempt->started_at?->toISOString(),
                'finished_at' => $attempt->finished_at?->toISOString(),
                'correct_count' => $locked->where('is_correct', true)->count(),
                'incorrect_count' => $locked->where('is_correct', false)->where('selected_choice', '!=', null)->count(),
                'unanswered_count' => $locked->whereNull('selected_choice')->count(),
                'avg_time_seconds' => round($locked->avg('time_taken_seconds'), 2),
            ],
            'answers' => $answerData,
        ];
    }

    public function getHistory(User|Admin $actor)
    {
        $fkField = $actor instanceof Admin ? 'admin_id' : 'user_id';

        return QuizAttempt::where($fkField, $actor->id)
            ->with('subject')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }
}
