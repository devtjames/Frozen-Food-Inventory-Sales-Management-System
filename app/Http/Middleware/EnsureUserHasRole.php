<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active, Response::HTTP_FORBIDDEN);
        abort_unless(in_array($user->role->value, $roles, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
