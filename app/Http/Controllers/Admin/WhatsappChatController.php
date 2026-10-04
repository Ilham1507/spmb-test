<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsappCloudApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappChatController extends Controller
{
    public function index(Request $request)
    {
        $phone = (string) $request->query('phone', '');
        abort_if($phone !== '' && ! preg_match('/^\d{8,20}$/D', $phone), 422);
        $conversations = DB::table('whatsapp_messages')->select('sender_phone')
            ->selectRaw('MAX(received_at) as last_at')->groupBy('sender_phone')
            ->orderByDesc('last_at')->limit(100)->get();
        $messages = collect();
        $open = false;
        $expires = null;
        if ($phone !== '') {
            $last = DB::table('whatsapp_messages')->where('sender_phone', $phone)->max('received_at');
            abort_unless($last, 404);
            $end = \Carbon\Carbon::parse($last)->addHours(23);
            $open = $end->isFuture();
            $expires = $end->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';
            $incoming = DB::table('whatsapp_messages')->where('sender_phone', $phone)
                ->orderByDesc('id')->limit(100)->get()->map(fn ($m) => [
                    'id' => 'in-'.$m->id, 'direction' => 'in',
                    'body' => $m->body ?? '['.$m->message_type.' — lampiran belum tersedia di menu ini]',
                    'at' => $m->received_at, 'status' => 'Masuk',
                ]);
            $outgoing = DB::table('whatsapp_chat_replies')->where('recipient_phone', $phone)
                ->orderByDesc('id')->limit(100)->get()->map(fn ($m) => [
                    'id' => 'out-'.$m->id, 'direction' => 'out', 'body' => $m->body,
                    'at' => $m->created_at, 'status' => match ($m->status) {
                        'accepted', 'sent' => 'Diterima API', 'delivered' => 'Terkirim ke WA',
                        'read' => 'Dibaca', 'failed' => 'Gagal', default => 'Diproses',
                    },
                ]);
            $messages = $incoming->merge($outgoing)->sortBy('at')->values();
        }
        $data = compact('conversations', 'messages', 'phone', 'open', 'expires');
        if ($request->expectsJson()) {
            return response()->json($data)->header('Cache-Control', 'no-store');
        }
        return response()->view('admin.whatsapp-chat', $data)->header('Cache-Control', 'no-store');
    }

    public function send(Request $request, WhatsappCloudApiService $whatsapp)
    {
        $data = $request->validate(['phone' => ['required', 'regex:/^\d{8,20}$/D'], 'body' => ['required', 'string', 'max:4000']]);
        abort_unless(DB::table('whatsapp_messages')->where('sender_phone', $data['phone'])->exists(), 404);
        if (! DB::table('whatsapp_messages')->where('sender_phone', $data['phone'])->where('received_at', '>', now()->subHours(23))->exists()) {
            return response()->json(['message' => 'Sesi balasan sudah berakhir. Tunggu penerima mengirim pesan baru ke nomor sekolah.'], 422);
        }
        $body = trim($data['body']);
        if ($body === '') {
            return response()->json(['message' => 'Isi pesan tidak boleh kosong.'], 422);
        }
        $id = DB::table('whatsapp_chat_replies')->insertGetId([
            'recipient_phone' => $data['phone'], 'admin_id' => $request->user()->id,
            'body' => $body, 'status' => 'sending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            $messageId = $whatsapp->sendChatReply($data['phone'], $body);
            DB::table('whatsapp_chat_replies')->where('id', $id)->update(['wa_message_id' => $messageId, 'status' => 'accepted', 'updated_at' => now()]);
        } catch (\Throwable $e) {
            DB::table('whatsapp_chat_replies')->where('id', $id)->update(['status' => 'failed', 'updated_at' => now()]);
            Log::warning('Balasan chat WhatsApp admin gagal.', ['reply_id' => $id]);
            return response()->json(['message' => 'Pengiriman belum berhasil dikonfirmasi. Periksa chat penerima sebelum mencoba lagi agar tidak ganda.'], 502);
        }
        return response()->json(['message' => 'Pesan diterima API. Status terkirim/dibaca mengikuti laporan WhatsApp.']);
    }
}
