<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            // Se for requisição AJAX/API retorna JSON
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            abort(403, 'Acesso negado.');
        }

        return $next($request);
    }
}
