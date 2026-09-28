<?php

use App\Models\Pendaftar;
use App\Models\TransaksiPembayaran;
use App\Services\WhatsappCloudApiService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

return new class extends Migration
{
    /** Send the actual official PDF document after uploading it to Waslah. */
    public function up(): void
    {
        try {
            $student = Pendaftar::query()->where('registration_number', 'SPMB2028-0003')->first();
            $transaction = $student
                ? TransaksiPembayaran::query()
                    ->with(['tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima'])
                    ->whereHas('tagihan', fn ($query) => $query->where('pendaftar_id', $student->id))
                    ->where('status', 'verified')->latest('id')->first()
                : null;

            if (! $transaction) {
                Log::warning('Invoice Ilham tidak dikirim: transaksi formulir terverifikasi tidak ditemukan.');
                return;
            }

            $student = $transaction->tagihan?->pendaftar;
            // The school explicitly requested this one-time resend to Ilham's
            // confirmed WhatsApp number, independent of any older test record.
            $phone = '082293094817';
            $receiver = $student?->kunjunganPenerimaanUtama()?->penerima;
            $receiverName = $receiver?->name ?? 'Panitia SPMB';
            $receiverPhone = (string) ($receiver?->phone ?: config('services.panitia.whatsapp_number'));
            $receiverContact = $receiverPhone !== '' ? "{$receiverName} ({$receiverPhone})" : $receiverName;
            $formUrl = URL::temporarySignedRoute('formulir.lanjut', now()->addMinutes(30));
            $pdfUrl = URL::temporarySignedRoute('invoice.public.pdf', now()->addMinutes(30), ['transaksi' => $transaction->id]);
            $caption = "Assalamu'alaikum wr. wb.\n\n"
                ."🎉 Selamat, anda berhasil melakukan pembayaran Formulir SPMB 🎉\n\n"
                ."Berikut terlampir bukti pembayaran.\n\n"
                ."Selanjutnya, silahkan kamu mengisi formulir pada link dibawah ini 👇🏻\n{$formUrl}\n\n"
                ."Jika ada kendala silahkan hubungi {$receiverContact}.\n\n"
                ."Terima kasih 🙏🏻\nSenang berkenalan denganmu 🌹";

            app(WhatsappCloudApiService::class)->sendDocument(
                $phone, $pdfUrl, 'invoice-spmb-'.$transaction->id.'.pdf', $caption,
            );
        } catch (Throwable $exception) {
            Log::warning('Invoice PDF resmi Ilham gagal dikirim.', ['error' => $exception->getMessage()]);
        }
    }

    public function down(): void {}
};
