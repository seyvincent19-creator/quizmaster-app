<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

class AnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'selected_choice' => ['nullable', 'string', 'in:A,B,C,D'],
            'time_taken_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'is_locked' => ['boolean'],
        ];
    }
}
