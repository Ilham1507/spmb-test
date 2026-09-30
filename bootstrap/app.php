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
        // Railway berada di belakang reverse proxy. Percayai header proxy agar
        // URL redirect/form selalu memakai HTTPS, bukan kembali ke HTTP.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST,
        );
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Logout hanya mengakhiri sesi dan sudah dibatasi oleh middleware auth.
        // Jangan tampilkan 419 saat pengguna menekan Keluar dari halaman yang
        // token CSRF-nya sudah usang; arahkan kembali ke login secara normal.
        $middleware->preventRequestForgery(except: ['logout']);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'panitia' => \App\Http\Middleware\PanitiaMiddleware::class,
            'bendahara' => \App\Http\Middleware\BendaharaMiddleware::class,
            'kepala_sekolah' => \App\Http\Middleware\KepalaSekolahMiddleware::class,
            'peserta' => \App\Http\Middleware\PesertaMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Railway only returns a blank 500 page in production. Keep a tightly
        // scoped diagnostic response for the major-fee management page so an
        // administrator can report the actual failing component; remove this
        // once the legacy database issue has been identified.
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('admin/biaya-jurusan') || ! auth()->check()) {
                return null;
            }

            $message = e(class_basename($exception).': '.$exception->getMessage());

            return response(<<<HTML
<!doctype html><html lang="id"><meta charset="utf-8"><title>Gangguan Biaya Jurusan</title>
<main style="max-width:760px;margin:64px auto;font-family:system-ui,sans-serif;padding:24px">
<h1 style="color:#991b1b">Biaya Jurusan belum dapat dibuka</h1>
<p>Detail gangguan untuk perbaikan:</p>
<pre style="white-space:pre-wrap;background:#fff1f2;border:1px solid #fecdd3;border-radius:12px;padding:16px;color:#881337">{$message}</pre>
<p>Silakan kirimkan teks ini ke pengembang. Tidak ada data yang diubah.</p>
</main></html>
HTML, 500);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi telah berakhir. Silakan masuk kembali.'], 419);
            }

            // Form promosi dapat diisi tanpa login. Jika sesi/CSRF kedaluwarsa,
            // kembalikan siswa ke formulir dengan pesan yang jelas, bukan login.
            if ($request->is('minat-promosi')) {
                return redirect()->route('promosi.minat.create')
                    ->with('error', 'Sesi formulir telah berakhir. Silakan isi kembali lalu kirimkan data.');
            }

            // A form left open after the thirty-minute idle logout must return
            // the user to login, not expose Laravel's generic 419 page.
            return redirect()->route('login', ['timeout' => 1]);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
