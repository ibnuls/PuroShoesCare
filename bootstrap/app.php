<?php

use App\Http\Middleware\ActiveStaff;
use App\Http\Middleware\PrivacyHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $m) {
        $m->web(append: [PrivacyHeaders::class]);
        $m->alias(['active' => ActiveStaff::class]);
        $m->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $e) {
        $e->dontFlash(['password', 'password_confirmation', 'password_hash', 'gemini_key']);
    })->create();
