<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportQuestionsRequest;
use App\Http\Requests\Admin\QuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Question::query()->with('subject');

        if ($request->filled('search')) {
            $query->where('question_text', 'like', "%{$request->search}%");
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $questions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'data' => QuestionResource::collection($questions->items())->map(fn($q) => array_merge(
                $q->toArray($request),
                [
                    'subject_id' => $q->subject_id,
                    'subject_name' => $q->subject?->name,
                    'correct_choice' => $q->correct_choice,
                    'explanation' => $q->explanation,
                ]
            )),
            'meta' => [
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'active_count' => Question::active()->count(),
            ],
        ]);
    }

    public function store(QuestionRequest $request): JsonResponse
    {
        $question = Question::create($request->validated());
        return response()->json($this->questionData($question), 201);
    }

    public function update(QuestionRequest $request, Question $question): JsonResponse
    {
        $question->update($request->validated());
        return response()->json($this->questionData($question->fresh()));
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully.']);
    }

    public function importJson(ImportQuestionsRequest $request): JsonResponse
    {
        $questions = collect($request->questions)->map(fn($q) => array_merge($q, [
            'is_active' => $q['is_active'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toArray();

        Question::insert($questions);

        return response()->json([
            'message' => count($questions) . ' questions imported successfully.',
            'count' => count($questions),
        ], 201);
    }

    private function questionData(Question $q): array
    {
        return [
            'id' => $q->id,
            'subject_id' => $q->subject_id,
            'subject_name' => $q->subject?->name,
            'question_text' => $q->question_text,
            'choice_a' => $q->choice_a,
            'choice_b' => $q->choice_b,
            'choice_c' => $q->choice_c,
            'choice_d' => $q->choice_d,
            'correct_choice' => $q->correct_choice,
            'explanation' => $q->explanation,
            'difficulty' => $q->difficulty,
            'category' => $q->category,
            'is_active' => $q->is_active,
            'created_at' => $q->created_at?->toISOString(),
        ];
    }
}
