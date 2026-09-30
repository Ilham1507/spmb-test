<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Keep a participant on the form they were completing when an unexpected
 * persistence/configuration problem happens. Validation failures are handled
 * by Laravel normally; this is only the last-resort guard against a 500 page.
 */
class HandleParticipantFormFailure
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $exception) {
            // Preserve field-level validation messages and ordinary HTTP
            // responses (403/404/419) from Laravel's normal handlers.
            if ($exception instanceof ValidationException || $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $exception;
            }

            Log::error('Formulir peserta gagal diproses.', [
                'method' => $request->method(),
                'path' => $request->path(),
                'user_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Data belum dapat disimpan. Silakan coba lagi.',
                ], 422);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Data belum dapat disimpan. Silakan coba lagi; isian Anda tetap dipertahankan.');
        }
    }
}
