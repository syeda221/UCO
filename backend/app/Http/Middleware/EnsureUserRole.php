<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Account is inactive or unauthorized.',
                ], 403);
            }

            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is inactive or unauthorized.',
            ]);
        }

        if ($user->role !== $role) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden for this role.',
                ], 403);
            }

            abort(403, 'Unauthorized role.');
        }

        return $next($request);
    }
}
