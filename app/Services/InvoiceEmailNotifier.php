<?php

namespace App\Services;

use App\Models\TransaksiPembayaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailNotifier
{
    /**
     * Send the exact verified-payment invoice to a participant's verified
     * contact email. WhatsApp remains the primary notification channel.
     */
    public function send(TransaksiPembayaran $transaction): bool
    {
        if ($transaction->status !== 'verified') {
            return false;
        }

        $transaction->loadMissing([
            'tagihan.jenisTagihan',
            'tagihan.pendaftar.biodata',
            'tagihan.pendaftar.user',
            'tagihan.pendaftar.kontak',
            'verifier',
            'treasurerReceiver',
        ]);

        $applicant = $transaction->tagihan?->pendaftar;
        $contact = $applicant?->kontak;
        if (! $contact?->email || ! $contact->email_verified_at) {
            return false;
        }

        if (config('mail.default') === 'log') {
            Log::info('Invoice email tidak dikirim karena mailer belum dikonfigurasi.', ['transaction_id' => $transaction->id]);
            return false;
        }

        try {
            $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
            $registrationNumber = $applicant?->registration_number ?: '-';
            $feeName = $transaction->tagihan?->jenisTagihan?->name ?? 'Pembayaran SPMB';
            $amount = number_format((float) $transaction->amount, 0, ',', '.');
            $filename = 'Bukti Pembayaran SPMB - '.($applicant?->registration_number ?: $transaction->id).'.pdf';
            $pdf = Pdf::loadView('payments.system-proof', ['transaction' => $transaction])
                ->setPaper('a4')
                ->output();

            Mail::send('emails.invoice-pembayaran', compact('student', 'registrationNumber', 'feeName', 'amount'), function ($message) use ($contact, $filename, $pdf) {
                $message->to($contact->email)
                    ->subject('Invoice Pembayaran SPMB')
                    ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
            });

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Pengiriman invoice pembayaran ke email gagal.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
