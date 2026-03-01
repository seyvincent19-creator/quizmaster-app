<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateUserOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokenable = $accessToken->tokenable;

        if ($tokenable instanceof Admin) {
            auth('admin')->setUser($tokenable);
        } else {
            // Regular user via Sanctum
            auth('sanctum')->setUser($tokenable);
        }

        return $next($request);
    }
}
