<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $expectedToken = (string) config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $expectedToken !== '' && hash_equals($expectedToken, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        abort(403, 'Token verifikasi webhook tidak sesuai.');
    }

    public function receive(Request $request): JsonResponse
    {
        Log::info('WhatsApp webhook received', [
            'object' => $request->input('object'),
            'entry' => $request->input('entry', []),
        ]);

        return response()->json(['received' => true]);
    }
}
