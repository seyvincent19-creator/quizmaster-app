<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuizAttemptResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('class_name')) {
            $query->where('class_name', $request->class_name);
        }

        if ($request->filled('generation')) {
            $query->where('generation', $request->generation);
        }

        $users = $query->withCount('quizAttempts')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(new UserResource($user->load('quizAttempts')));
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message' => $user->is_active ? 'User activated.' : 'User deactivated.',
            'is_active' => $user->is_active,
            'user' => new UserResource($user),
        ]);
    }

    public function classOptions(): JsonResponse
    {
        $classes = User::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return response()->json($classes);
    }

    public function generationOptions(): JsonResponse
    {
        $generations = User::whereNotNull('generation')
            ->where('generation', '!=', '')
            ->distinct()
            ->orderBy('generation')
            ->pluck('generation');

        return response()->json($generations);
    }

    public function attempts(User $user): JsonResponse
    {
        $attempts = $user->quizAttempts()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => QuizAttemptResource::collection($attempts->items()),
            'meta' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'total' => $attempts->total(),
                'per_page' => $attempts->perPage(),
            ],
        ]);
    }
}
