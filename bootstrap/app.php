<?php

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
        $middleware->alias([
            'user.permission' => \App\Http\Middleware\CheckUserPermission::class,
            'project.access' => \App\Http\Middleware\CheckProjectAccess::class,
            'admin.role'      => \App\Http\Middleware\AdminRoleMiddleware::class, // ✅ fixed
            'check.access' => \App\Http\Middleware\CheckAccess::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
