<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_choice',
        'is_correct',
        'answered_at',
        'time_taken_seconds',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'is_locked' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function quizAttempt()
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
