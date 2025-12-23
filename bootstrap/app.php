<?php

use App\Http\Middleware\CheckAppsId;
use Illuminate\Foundation\Application;
use App\Http\Middleware\Role\SalesMiddleware;
use App\Http\Middleware\Role\CustomerMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
          $middleware->alias([
            'sales'    => App\Http\Middleware\Role\SalesMiddleware::class,
            'customer' => App\Http\Middleware\Role\CustomerMiddleware::class,
            'apps_id'  => App\Http\Middleware\CheckAppsId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
