<?php

use App\Models\TransaksiPembayaran;
use App\Services\WhatsappCloudApiService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

return new class extends Migration
{
    /** Deliver the one approval notification that failed before the fallback existed. */
    public function up(): void
    {
        try {
            $transaction = TransaksiPembayaran::query()
                ->with(['tagihan.jenisTagihan', 'tagihan.pendaftar.user', 'tagihan.pendaftar.biodata'])
                ->where('status', 'verified')
                ->whereHas('tagihan.pendaftar', fn ($query) => $query->where('registration_number', 'SPMB2028-0006'))
                ->latest('id')
                ->first();

            if (! $transaction) {
                Log::warning('Kirim ulang WhatsApp Ilham dilewati: transaksi terverifikasi tidak ditemukan.');
                return;
            }

            $applicant = $transaction->tagihan?->pendaftar;
            $phone = (string) ($applicant?->user?->phone ?? '');
            if ($phone === '') {
                Log::warning('Kirim ulang WhatsApp Ilham dilewati: nomor WhatsApp tidak tersedia.', ['transaction_id' => $transaction->id]);
                return;
            }

            $formUrl = URL::temporarySignedRoute('formulir.lanjut', now()->addMinutes(30));
            $message = "Assalamu'alaikum wr. wb.\n\n"
                ."Pembayaran Formulir SPMB kamu telah disetujui.\n\n"
                ."Silakan lanjutkan pengisian formulir melalui link berikut:\n{$formUrl}\n\n"
                ."Terima kasih.";
            $filename = 'Bukti Pembayaran SPMB - '.($applicant?->registration_number ?: $transaction->id).'.pdf';
            $pdfUrl = URL::temporarySignedRoute('invoice.public.pdf', now()->addMinutes(30), ['transaksi' => $transaction->id]);
            $whatsapp = app(WhatsappCloudApiService::class);

            try {
                $whatsapp->sendDocument($phone, $pdfUrl, $filename, $message);
            } catch (\Throwable $exception) {
                Log::warning('Lampiran invoice Ilham gagal; mengirim notifikasi teks.', [
                    'transaction_id' => $transaction->id,
                    'error' => $exception->getMessage(),
                ]);
                $whatsapp->send($phone, $message);
            }
        } catch (\Throwable $exception) {
            Log::warning('Kirim ulang WhatsApp Ilham gagal.', ['error' => $exception->getMessage()]);
        }
    }

    public function down(): void {}
};
