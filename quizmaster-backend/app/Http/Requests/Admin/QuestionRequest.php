<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'question_text' => ['required', 'string'],
            'choice_a' => ['required', 'string'],
            'choice_b' => ['required', 'string'],
            'choice_c' => ['required', 'string'],
            'choice_d' => ['required', 'string'],
            'correct_choice' => ['required', 'string', 'in:A,B,C,D'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', 'string', 'in:easy,medium,hard'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
