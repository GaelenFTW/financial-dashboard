<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'user.permission' => \App\Http\Middleware\CheckUserPermission::class,
            'admin.role'      => \App\Http\Middleware\AdminRoleMiddleware::class,
            'check.group'     => \App\Http\Middleware\CheckGroupAccess::class,
            'can.menu'        => \App\Http\Middleware\CheckMenuAccess::class,
            'role'            => \App\Http\Middleware\RoleMiddleware::class,
            'permission'      => \App\Http\Middleware\CheckPermissionMiddleware::class,
            'project.access'  => \App\Http\Middleware\CheckProjectAccessMiddleware::class,
            'rbac'            => \App\Http\Middleware\CheckRBACPermission::class,

        ]);


    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
