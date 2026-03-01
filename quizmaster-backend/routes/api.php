<?php

use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::get('subjects', [SubjectController::class, 'index']);

// ==========================================
// USER AUTH ROUTES
// ==========================================
Route::prefix('auth')->group(function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('login', [UserAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [UserAuthController::class, 'logout']);
        Route::get('me', [UserAuthController::class, 'me']);
        Route::put('profile', [UserAuthController::class, 'updateProfile']);
        Route::put('password', [UserAuthController::class, 'changePassword']);
    });
});

// ==========================================
// QUIZ ROUTES (User or Admin)
// ==========================================
Route::prefix('quiz')->middleware('auth.quiz')->group(function () {
    Route::post('start', [QuizController::class, 'start']);
    Route::get('history', [QuizController::class, 'history']);
    Route::get('stats', [QuizController::class, 'stats']);
    Route::get('{attemptCode}', [QuizController::class, 'resume']);
    Route::post('{attemptCode}/answer', [QuizController::class, 'answer']);
    Route::post('{attemptCode}/finish', [QuizController::class, 'finish']);
    Route::get('{attemptCode}/result', [QuizController::class, 'result'])->name('quiz.result');
    Route::get('{attemptCode}/report/pdf', [QuizController::class, 'downloadPdf']);
    Route::get('{attemptCode}/report/excel', [QuizController::class, 'downloadExcel']);
});

// ==========================================
// ADMIN AUTH ROUTES
// ==========================================
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::middleware('auth.admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);

        // Questions
        Route::get('questions', [QuestionController::class, 'index']);
        Route::post('questions', [QuestionController::class, 'store']);
        Route::put('questions/{question}', [QuestionController::class, 'update']);
        Route::delete('questions/{question}', [QuestionController::class, 'destroy']);
        Route::post('questions/import-json', [QuestionController::class, 'importJson']);

        // Users
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/class-options', [UserController::class, 'classOptions']);
        Route::get('users/generation-options', [UserController::class, 'generationOptions']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::get('users/{user}/attempts', [UserController::class, 'attempts']);

        // Reports
        Route::get('reports/summary', [ReportController::class, 'summary']);
        Route::get('reports/attempts', [ReportController::class, 'attempts']);
        Route::get('reports/questions/analysis', [ReportController::class, 'questionAnalysis']);
        Route::get('reports/by-class', [ReportController::class, 'byClass']);
        Route::get('reports/by-generation', [ReportController::class, 'byGeneration']);
        Route::get('reports/export/excel', [ReportController::class, 'exportExcel']);
        Route::get('reports/export/pdf', [ReportController::class, 'exportPdf']);

        // Subjects
        Route::get('subjects', [AdminSubjectController::class, 'index']);
        Route::post('subjects', [AdminSubjectController::class, 'store']);
        Route::put('subjects/{subject}', [AdminSubjectController::class, 'update']);
        Route::delete('subjects/{subject}', [AdminSubjectController::class, 'destroy']);
        Route::put('subjects/{subject}/toggle-active', [AdminSubjectController::class, 'toggleActive']);
    });
});
