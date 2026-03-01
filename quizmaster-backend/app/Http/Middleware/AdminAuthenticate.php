<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !($accessToken->tokenable instanceof Admin)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Set the authenticated admin on auth('admin') guard
        auth('admin')->setUser($accessToken->tokenable);

        return $next($request);
    }
}
