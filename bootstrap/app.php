<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\PastikanAdmin::class,
        ]);

        // Percayai header proxy (X-Forwarded-Proto) supaya aset dirender
        // sebagai https:// saat diakses lewat tunnel.
        $middleware->trustProxies(at: '*');

        $middleware->prepend(\App\Http\Middleware\SinkronkanAppUrl::class);

        // Halaman masuk di app ini bernama 'masuk', bukan 'login'.
        $middleware->redirectGuestsTo(fn () => route('masuk'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
