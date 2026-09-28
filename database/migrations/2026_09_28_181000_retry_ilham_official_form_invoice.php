<?php

use App\Models\Pendaftar;
use App\Models\TransaksiPembayaran;
use App\Services\WhatsappCloudApiService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

return new class extends Migration
{
    /** Retry the official form-payment document after Waslah instance discovery is enabled. */
    public function up(): void
    {
        try {
            $pendaftar = Pendaftar::query()
                ->where('registration_number', 'SPMB2028-0003')
                ->first();

            $transaction = $pendaftar
                ? TransaksiPembayaran::query()
                    ->with(['tagihan.jenisTagihan', 'tagihan.pendaftar.biodata', 'tagihan.pendaftar.user', 'tagihan.pendaftar.kunjungan.penerima', 'verifier'])
                    ->whereHas('tagihan', fn ($query) => $query->where('pendaftar_id', $pendaftar->id))
                    ->where('status', 'verified')
                    ->latest('id')
                    ->first()
                : null;

            if (! $transaction) {
                Log::warning('Pengiriman invoice resmi Ilham dilewati: transaksi formulir belum ditemukan.');
                return;
            }

            $student = $transaction->tagihan?->pendaftar;
            $phone = (string) ($student?->user?->phone ?? '');
            if ($phone === '') {
                Log::warning('Pengiriman invoice resmi Ilham dilewati: nomor WhatsApp kosong.', ['transaction_id' => $transaction->id]);
                return;
            }

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
                $phone,
                $pdfUrl,
                'invoice-spmb-'.$transaction->id.'.pdf',
                $caption,
            );
        } catch (Throwable $exception) {
            Log::warning('Pengiriman ulang invoice resmi Ilham gagal.', ['error' => $exception->getMessage()]);
        }
    }

    public function down(): void
    {
        // The notification is intentional and does not modify payment records.
    }
};
