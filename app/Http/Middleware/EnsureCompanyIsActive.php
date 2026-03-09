<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && (!$user->company || !$user->company->active)) {
            Auth::logout();

            return redirect('/login')->withErrors([
                'email' => 'Sua empresa está inativa. Entre em contato com o suporte.'
            ]);
        }

        return $next($request);
    }
}
