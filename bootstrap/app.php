<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureCompanyIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // Middlewares globais aplicados em todas as requisições autenticadas
        $middleware->appendToGroup('web', EnsureCompanyIsActive::class);

        // Aliases para usar nas rotas
        $middleware->alias([
            'role'           => CheckRole::class,
            'company.active' => EnsureCompanyIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

