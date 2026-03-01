<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.choice_a' => ['required', 'string'],
            'questions.*.choice_b' => ['required', 'string'],
            'questions.*.choice_c' => ['required', 'string'],
            'questions.*.choice_d' => ['required', 'string'],
            'questions.*.correct_choice' => ['required', 'string', 'in:A,B,C,D'],
            'questions.*.explanation' => ['nullable', 'string'],
            'questions.*.difficulty' => ['required', 'string', 'in:easy,medium,hard'],
            'questions.*.category' => ['nullable', 'string'],
        ];
    }
}
