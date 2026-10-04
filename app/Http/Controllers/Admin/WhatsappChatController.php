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
        $conversationRows = DB::table('whatsapp_messages')->selectRaw('sender_phone, received_at as at')
            ->unionAll(DB::table('whatsapp_chat_replies')->selectRaw('recipient_phone as sender_phone, created_at as at'));
        $conversations = DB::query()->fromSub($conversationRows, 'chat_rows')->select('sender_phone')
            ->selectRaw('MAX(at) as last_at')->groupBy('sender_phone')->orderByDesc('last_at')->limit(100)->get();
        $phones = $conversations->pluck('sender_phone')->push($phone)->filter()->unique();
        $variants = $phones->flatMap(fn ($number) => [$number, str_starts_with($number, '62') ? '0'.substr($number, 2) : $number])->unique()->values()->all();
        $names = collect();
        if ($variants && \Illuminate\Support\Facades\Schema::hasTable('pengguna')) {
            $names = DB::table('pengguna')->select('name', 'phone')->whereIn(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '')"), $variants)
                ->get()->mapWithKeys(function ($user) {
                    $number = preg_replace('/\D/', '', (string) $user->phone);
                    if (str_starts_with($number, '0')) { $number = '62'.substr($number, 1); }
                    return [$number => $user->name];
                });
        }
        $conversations->each(fn ($contact) => $contact->name = $names->get($contact->sender_phone, 'Nomor belum terdaftar'));
        $contactName = $names->get($phone, 'Nomor belum terdaftar');
        $messages = collect();
        $open = false;
        $expires = null;
        if ($phone !== '') {
            $last = DB::table('whatsapp_messages')->where('sender_phone', $phone)->max('received_at');
            abort_unless($last || DB::table('whatsapp_chat_replies')->where('recipient_phone', $phone)->exists(), 404);
            if ($last) {
                $end = \Carbon\Carbon::parse($last)->addHours(23);
                $open = $end->isFuture();
                $expires = $end->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB';
            }
            $incoming = DB::table('whatsapp_messages')->where('sender_phone', $phone)
                ->orderByDesc('id')->limit(100)->get()->map(fn ($m) => [
                    'id' => 'in-'.$m->id, 'direction' => 'in',
                    'body' => $m->body ?? '['.$m->message_type.' — lampiran belum tersedia di menu ini]',
                    'at' => $m->received_at, 'status' => 'Masuk',
                ]);
            $outgoing = DB::table('whatsapp_chat_replies')->where('recipient_phone', $phone)
                ->orderByDesc('id')->limit(100)->get()->map(fn ($m) => [
                    'id' => 'out-'.$m->id, 'direction' => 'out', 'body' => $m->body,
                    'at' => $m->created_at, 'status' => ((int) $m->admin_id === 0 ? 'Otomatis · ' : '').match ($m->status) {
                        'accepted', 'sent' => 'Diterima API', 'delivered' => 'Terkirim ke WA',
                        'read' => 'Dibaca', 'failed' => 'Gagal', default => 'Diproses',
                    },
                ]);
            $messages = $incoming->merge($outgoing)->sortBy('at')->values();
        }
        $data = compact('conversations', 'messages', 'phone', 'open', 'expires', 'contactName');
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
