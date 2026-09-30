<?php

namespace App\Services;

use App\Models\TransaksiPembayaran;
use Illuminate\Support\Facades\Log;

class PaymentReceiptNotifier
{
    public function __construct(private WhatsappCloudApiService $whatsapp) {}

    /** Send only after a payment has changed to verified. */
    public function send(TransaksiPembayaran $transaction): bool
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
            // The teacher who received the visit owns the first follow-up,
            // not a generic administrator number.
            $target = (string) ($visit?->penerima?->phone ?: config('services.panitia.whatsapp_number'));
            if ($target === '') return false;

            $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
            $feeNames = $this->feeSummary($transaction);
            $receivedBy = $transaction->verifier?->name ?? 'Panitia SPMB';
            $visitReceiver = $visit?->penerima?->name ?? 'Panitia SPMB';
            $channel = ['cash' => 'Tunai di sekolah', 'transfer' => 'Transfer bank'][$transaction->payment_method] ?? 'Pembayaran manual';
            $registrationNumber = $applicant?->registration_number ?: '-';
            $amount = number_format((float) $transaction->amount, 0, ',', '.');
            $remaining = number_format((float) ($bill?->remaining_amount ?? 0), 0, ',', '.');

            $this->whatsapp->send($target, \App\Support\WhatsappGreeting::opening()."\n\n"
                ."Informasi pembayaran SPMB\n\nSiswa: {$student}\nNo. pendaftaran: {$registrationNumber}\nBiaya: {$feeNames}\nNominal: Rp {$amount}\nMetode: {$channel}\nPenerima kunjungan: {$visitReceiver}\nDiverifikasi oleh: {$receivedBy}\nSisa tagihan: Rp {$remaining}");
            return true;
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi penerimaan pembayaran ke penerima kunjungan gagal dikirim.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Notify the teacher/panitia who received the applicant's visit as soon
     * as a student uploads a transfer proof. This is deliberately separate
     * from send(): the payment is still pending and must not be described as
     * verified to either staff or the student.
     */
    public function notifyApprovalNeeded(TransaksiPembayaran $transaction): bool
    {
        try {
            $transaction->loadMissing([
                'tagihan.jenisTagihan',
                'tagihan.pendaftar.biodata',
                'tagihan.pendaftar.user',
                'tagihan.pendaftar.kunjungan.penerima',
            ]);

            $bill = $transaction->tagihan;
            $applicant = $bill?->pendaftar;
            $visit = $applicant?->kunjunganPenerimaanUtama();
            $target = trim((string) ($visit?->penerima?->phone ?: config('services.panitia.whatsapp_number')));
            if ($target === '') {
                throw new \RuntimeException('Nomor WhatsApp penerima kunjungan belum tersedia.');
            }

            $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
            $feeNames = $this->feeSummary($transaction);
            $registrationNumber = $applicant?->registration_number ?: '-';
            $amount = number_format((float) $transaction->amount, 0, ',', '.');
            $receiver = $visit?->penerima?->name ?? 'Panitia SPMB';
            $approvalUrl = route('panitia.pembayaran.index');

            $this->whatsapp->send($target, \App\Support\WhatsappGreeting::opening()."\n\n"
                ."Bukti transfer baru menunggu approval.\n\n"
                ."Siswa: {$student}\nNo. pendaftaran: {$registrationNumber}\nBiaya: {$feeNames}\nNominal: Rp {$amount}\nMetode: Transfer\nPenerima kunjungan: {$receiver}\n\n"
                ."Silakan periksa bukti dan setujui pembayaran di:\n{$approvalUrl}");

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Notifikasi bukti pembayaran baru ke penerima kunjungan gagal dikirim.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Keep WhatsApp concise when a student pays many re-registration items.
     * The complete item list remains available in the payment detail.
     */
    private function feeSummary(TransaksiPembayaran $transaction): string
    {
        $items = collect($transaction->selected_items ?? [])
            ->pluck('name')
            ->filter(fn ($name) => filled($name))
            ->unique()
            ->values();

        if ($items->isEmpty()) {
            return $transaction->tagihan?->jenisTagihan?->name ?? 'Tagihan sekolah';
        }

        if ($items->count() === 1) {
            return (string) $items->first();
        }

        $paymentType = $transaction->tagihan?->jenisTagihan?->name ?? 'Tagihan sekolah';

        return $paymentType.' — '.$items->count().' rincian biaya terpilih';
    }
}
