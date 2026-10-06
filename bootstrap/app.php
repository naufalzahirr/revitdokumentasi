<?php

use App\Http\Middleware\ActiveAccount;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\PasswordChanged;
use App\Http\Middleware\PrivateResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.active' => ActiveAccount::class,
            'password.changed' => PasswordChanged::class,
            'admin' => AdminOnly::class,
            'private' => PrivateResponse::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
