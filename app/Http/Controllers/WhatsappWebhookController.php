<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        $secret = trim((string) config('services.whatsapp.app_secret'));
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        abort_unless($secret !== '' && hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $signature), 403, 'Signature webhook WhatsApp tidak valid.');
        abort_unless($request->input('object') === 'whatsapp_business_account', 400);
        $stored = 0;
        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                foreach ((array) data_get($change, 'value.messages', []) as $message) {
                    if (!filled(data_get($message, 'id')) || !filled(data_get($message, 'from'))) { continue; }
                    $stored += DB::table('whatsapp_messages')->insertOrIgnore([
                        'wa_message_id' => $message['id'], 'sender_phone' => $message['from'],
                        'message_type' => data_get($message, 'type', 'unknown'),
                        'body' => data_get($message, 'text.body') ?? data_get($message, 'button.text') ?? data_get($message, 'interactive.button_reply.title') ?? data_get($message, 'interactive.list_reply.title'),
                        'payload' => json_encode($message, JSON_THROW_ON_ERROR),
                        'received_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                foreach ((array) data_get($change, 'value.statuses', []) as $status) {
                    $delivery = data_get($status, 'status');
                    if (in_array($delivery, ['sent', 'delivered', 'read', 'failed'], true)
                        && \Illuminate\Support\Facades\Schema::hasTable('whatsapp_chat_replies')) {
                        $reply = DB::table('whatsapp_chat_replies')->where('wa_message_id', data_get($status, 'id'));
                        $current = (clone $reply)->value('status');
                        $rank = ['sending' => 0, 'accepted' => 1, 'sent' => 2, 'delivered' => 3, 'read' => 4, 'failed' => 5];
                        if ($current && ($rank[$delivery] ?? 0) > ($rank[$current] ?? 0)) {
                            $reply->where('status', $current)->update(['status' => $delivery, 'updated_at' => now()]);
                        }
                    }
                    Log::info('WhatsApp delivery status', [
                        'message_id' => data_get($status, 'id'), 'status' => data_get($status, 'status'),
                        'error_codes' => collect(data_get($status, 'errors', []))->pluck('code')->all(),
                    ]);
                }
            }
        }
        Log::info('WhatsApp webhook received', [
            'object' => $request->input('object'),
            'stored_messages' => $stored,
        ]);

        return response()->json(['received' => true]);
    }
}
