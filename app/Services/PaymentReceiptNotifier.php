<?php

namespace App\Services;

use App\Models\TransaksiPembayaran;
use Illuminate\Support\Facades\Log;

class PaymentReceiptNotifier
{
    public function __construct(private WhatsappCloudApiService $whatsapp) {}

    /** Send only after a payment has changed to verified. */
    public function send(TransaksiPembayaran $transaction): void
    {
        try {
            $transaction->loadMissing([
                'tagihan.jenisTagihan',
                'tagihan.pendaftar.biodata',
                'tagihan.pendaftar.user',
                'tagihan.pendaftar.kunjungan.penerima',
                'verifier',
            ]);
            $bill = $transaction->tagihan;
            $applicant = $bill?->pendaftar;
            $visit = $applicant?->kunjunganPenerimaanUtama();
            $target = (string) config('services.payments.admin_whatsapp_number');
            $target = $target ?: ($visit?->penerima?->phone ?: (string) config('services.panitia.whatsapp_number'));
            if ($target === '') return;

            $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
            $feeNames = collect($transaction->selected_items ?? [])->pluck('name')->filter()->join(', ')
                ?: ($bill?->jenisTagihan?->name ?? 'Tagihan sekolah');
            $receivedBy = $transaction->verifier?->name ?? 'Panitia SPMB';
            $visitReceiver = $visit?->penerima?->name ?? 'Panitia SPMB';
            $channel = ['cash' => 'Tunai di sekolah', 'transfer' => 'Transfer bank'][$transaction->payment_method] ?? 'Pembayaran manual';
            $registrationNumber = $applicant?->registration_number ?: '-';
            $amount = number_format((float) $transaction->amount, 0, ',', '.');
            $remaining = number_format((float) ($bill?->remaining_amount ?? 0), 0, ',', '.');

            $this->whatsapp->send($target, "Pembayaran SPMB diterima\n\nSiswa: {$student}\nNo. pendaftaran: {$registrationNumber}\nBiaya: {$feeNames}\nNominal: Rp {$amount}\nMetode: {$channel}\nPenerima kunjungan: {$visitReceiver}\nDiverifikasi oleh: {$receivedBy}\nSisa tagihan: Rp {$remaining}");
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi penerimaan pembayaran ke admin gagal dikirim.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
