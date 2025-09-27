<?php

use App\Http\Middleware\PermitTask;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\Route;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::namespace('App\Http\Controllers')
                ->middleware(['web'])
                ->group(base_path('routes/web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/*',
        ]);
        $middleware->alias(['permit.task' =>PermitTask::class]);

    })->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();