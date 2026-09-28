<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->prefix('panitia')
                ->name('panitia.')
                ->group(base_path('routes/panitia.php'));

            Route::middleware('web')
                ->prefix('bendahara')
                ->name('bendahara.')
                ->group(base_path('routes/bendahara.php'));

            Route::middleware('web')
                ->prefix('kepala-sekolah')
                ->name('kepala-sekolah.')
                ->group(base_path('routes/kepala-sekolah.php'));

            Route::middleware('web')
                ->prefix('peserta')
                ->name('peserta.')
                ->group(base_path('routes/peserta.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Local tunnel connectors forward the public hostname and HTTPS scheme.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1'],
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST,
        );
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Logout hanya mengakhiri sesi dan sudah dibatasi oleh middleware auth.
        // Jangan tampilkan 419 saat pengguna menekan Keluar dari halaman yang
        // token CSRF-nya sudah usang; arahkan kembali ke login secara normal.
        $middleware->preventRequestForgery(except: ['logout', '_internal/railway-sync/*']);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'panitia' => \App\Http\Middleware\PanitiaMiddleware::class,
            'bendahara' => \App\Http\Middleware\BendaharaMiddleware::class,
            'kepala_sekolah' => \App\Http\Middleware\KepalaSekolahMiddleware::class,
            'peserta' => \App\Http\Middleware\PesertaMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi telah berakhir. Silakan masuk kembali.'], 419);
            }

            // A form left open after the five-minute idle logout must return
            // the user to login, not expose Laravel's generic 419 page.
            return redirect()->route('login', ['timeout' => 1]);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
