<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'subject_id',
        'attempt_code',
        'started_at',
        'finished_at',
        'score',
        'total_questions',
        'status',
        'current_index',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(\App\Models\Admin::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function getIncorrectCountAttribute(): int
    {
        return $this->answers()->where('is_locked', true)->where('is_correct', false)->count();
    }

    public function getUnansweredCountAttribute(): int
    {
        return $this->answers()->where('is_locked', true)->whereNull('selected_choice')->count();
    }
}
