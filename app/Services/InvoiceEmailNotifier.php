<?php

namespace App\Services;

use App\Models\InvoiceEmailDelivery;
use App\Models\KontakPendaftar;
use App\Models\TransaksiPembayaran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailNotifier
{
    /** Catch up payments approved before the participant verified their email. */
    public function sendVerifiedForContact(KontakPendaftar $contact): void
    {
        if (! $contact->email || ! $contact->email_verified_at) {
            return;
        }

        TransaksiPembayaran::query()
            ->where('status', 'verified')
            ->whereHas('tagihan', fn ($query) => $query->where('applicant_id', $contact->applicant_id))
            ->chunkById(50, function ($transactions) {
                foreach ($transactions as $transaction) {
                    $this->send($transaction);
                }
            });
    }

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
            $delivery = InvoiceEmailDelivery::firstOrCreate([
                'transaction_id' => $transaction->id,
                'recipient' => strtolower(trim($contact->email)),
            ]);

            return DB::transaction(function () use ($delivery, $transaction) {
                $delivery = InvoiceEmailDelivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
                if ($delivery->sent_at) {
                    return false;
                }

                $this->deliver($transaction);
                $delivery->update(['sent_at' => now()]);

                return true;
            });
        } catch (\Throwable $exception) {
            Log::warning('Pengiriman invoice pembayaran ke email gagal.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function deliver(TransaksiPembayaran $transaction): void
    {
        $applicant = $transaction->tagihan->pendaftar;
        $contact = $applicant->kontak;
        $student = $applicant?->biodata?->full_name ?? $applicant?->user?->name ?? 'Calon siswa';
        $registrationNumber = $applicant?->registration_number ?: '-';
        $feeName = $transaction->tagihan?->jenisTagihan?->name ?? 'Pembayaran SPMB';
        $amount = number_format((float) $transaction->amount, 0, ',', '.');
        $filename = \App\Support\PaymentProof::filename($transaction);
        $pdf = Pdf::loadView('payments.system-proof', ['transaction' => $transaction])
            ->setPaper('a4')
            ->output();

        Mail::send('emails.invoice-pembayaran', compact('student', 'registrationNumber', 'feeName', 'amount'), function ($message) use ($contact, $filename, $pdf) {
            $message->to($contact->email)
                ->subject('Invoice Pembayaran SPMB')
                ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
        });

    }
}
