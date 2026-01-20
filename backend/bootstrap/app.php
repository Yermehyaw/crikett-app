<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['api'])
                ->prefix('api/admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['api'])
                ->prefix('api/user')
                ->name('user.')
                ->group(base_path('routes/user.php'));
        }
    )

    ->withMiddleware(function (Middleware $middleware) {

        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'broadcasting/auth',
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->is('broadcasting/auth')) {
                throw new AuthenticationException('Unauthenticated');
            }
            if (Route::has('login')) {
                return route('login');
            }

            return '/login';
        });

        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);

        $middleware->group('api', [
            // 'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'user' => \App\Http\Middleware\UserMiddleware::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReportDuplicates();

        $exceptions->renderable(function (NotFoundHttpException|ModelNotFoundException $e) {
            if (request()->is('api/*')) {
                return new JsonResponse([
                    'code' => 404,
                    'message' => 'Sorry, We Can\'t Find That Resource',
                    'error' => 'Resource not found',
                    'success' => false,
                ], 404);
            }

            return request()->expectsJson();
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->is('broadcasting/auth')) {
                return new JsonResponse([
                    'code' => 401,
                    'message' => 'Authentication required',
                    'error' => 'Unauthenticated',
                    'success' => false,
                ], 401);
            }
        });

        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->is('broadcasting/auth')) {
                return new JsonResponse([
                    'code' => 403,
                    'message' => 'Access denied. You are not authorized to access this resource.',
                    'error' => 'Forbidden',
                    'success' => false,
                ], 403);
            }
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });
    })->create();
