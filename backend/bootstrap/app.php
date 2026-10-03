<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Habilita o suporte a cookies stateful para o Sanctum nas rotas de API
        $middleware->statefulApi();

        // Desativa a checagem APENAS se o projeto estiver rodando localmente em ambiente de testes
        // env('APP_ENV') 
        if (\Illuminate\Support\Env::get('APP_ENV') === 'local') {
            // Remove a criptografia apenas do cookie XSRF-TOKEN
            $middleware->encryptCookies(except: [
                'XSRF-TOKEN',
            ]);
            $middleware->validateCsrfTokens(except: ['api/*']);
        }
        
    })


    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
