<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'choice_a' => $this->choice_a,
            'choice_b' => $this->choice_b,
            'choice_c' => $this->choice_c,
            'choice_d' => $this->choice_d,
            'correct_choice' => $this->when($this->shouldShowAnswer($request), $this->correct_choice),
            'explanation' => $this->when($this->shouldShowAnswer($request), $this->explanation),
            'difficulty' => $this->difficulty,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function shouldShowAnswer(Request $request): bool
    {
        // Show correct answer only in review/result context or admin context
        return $request->is('api/admin/*') || $request->routeIs('quiz.result') || $request->routeIs('quiz.review');
    }
}
