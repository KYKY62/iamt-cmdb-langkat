<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.api_token' => \App\Http\Middleware\AuthenticateApiToken::class,
            'role.full' => \App\Http\Middleware\EnsureFullAccess::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            $sqlState = $exception->errorInfo[0] ?? null;
            $driverCode = (string) ($exception->errorInfo[1] ?? '');
            $message = $exception->getMessage();
            $isDeleteConstraint = $request->isMethod('delete')
                && $sqlState === '23000'
                && ($driverCode === '1451' || str_contains(strtolower($message), 'foreign key constraint'));

            if ($isDeleteConstraint && $request->is('api/*')) {
                return response()->json([
                    'message' => 'Terdapat data dibawah entitas ini. Hapus atau lepaskan relasi data terkait terlebih dahulu sebelum menghapus data utama.',
                    'type' => 'constraint_violation',
                ], 409);
            }

            return null;
        });
    })
    ->create();
