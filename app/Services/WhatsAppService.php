<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Siswa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    // Adapter siap diganti ke provider WA resmi; saat kredensial belum dipasang, pesan dicatat aman di log.
    public function paymentUpdate(Payment $payment): void
    {
        $payment->loadMissing('siswa', 'approver', 'treasurer');
        $approved = $payment->approver?->name ?? 'belum disetujui panitia';
        $received = $payment->treasurer ? 'Sudah diterima bendahara: '.$payment->treasurer->name : 'Belum diterima bendahara';
        $message = "Invoice {$payment->invoice_number}; disetujui: {$approved}; {$received}";
        $pdfUrl = URL::temporarySignedRoute('payments.invoice.pdf.public', now()->addDays(7), ['payment' => $payment->id]);
        $payload = ['phone' => $payment->siswa->phone, 'message' => $message, 'invoice_url' => route('payments.invoice', $payment), 'pdf_url' => $pdfUrl];
        $this->send($payload, 'payment');
    }
    public function activation(Siswa $siswa): void
    {
        if ($siswa->user?->account_activated_at) {
            $url = URL::temporarySignedRoute('candidate-form.show', now()->addMinutes(5), ['siswa' => $siswa->id]);
            $this->send(['phone' => $siswa->phone, 'message' => "Pembayaran formulir Anda tercatat. Silakan isi formulir melalui tautan ini: {$url}"], 'form-link');
            return;
        }
        $url = URL::temporarySignedRoute('activation.show', now()->addMinutes(5), ['siswa' => $siswa->id]);
        $this->send(['phone' => $siswa->phone, 'message' => "Pembayaran formulir Anda tercatat. Silakan lanjutkan login/isi formulir melalui tautan ini: {$url}"], 'activation');
    }
    private function send(array $payload, string $type): void
    {
        if (config('services.whatsapp.provider') === 'waslah') {
            $token = config('services.whatsapp.waslah_token');
            $instance = config('services.whatsapp.waslah_instance_key');
            if (!$token || !$instance) { Log::channel('daily')->info('WA '.$type.' notification (Waslah belum dikonfigurasi)', $payload); return; }
            try {
                $client = Http::withToken($token)->acceptJson();
                $client->post('https://waslah.id/api/v1/messages/text', ['instance_key' => $instance, 'to' => $payload['phone'], 'text' => $payload['message']])->throw();
                if (!empty($payload['pdf_url'])) $client->post('https://waslah.id/api/v1/messages/media', ['instance_key' => $instance, 'to' => $payload['phone'], 'type' => 'document', 'url' => $payload['pdf_url'], 'filename' => 'Invoice-SPMB.pdf', 'mimetype' => 'application/pdf', 'caption' => 'Invoice pembayaran SPMB'])->throw();
                return;
            } catch (\Throwable $e) { Log::warning('Waslah gagal; pembayaran tetap tersimpan.', ['error' => $e->getMessage(), 'phone' => $payload['phone']]); return; }
        }
        $url = config('services.whatsapp.webhook_url');
        if ($url) { try { Http::withToken(config('services.whatsapp.token'))->post($url, $payload)->throw(); return; } catch (\Throwable $e) { Log::warning('WA gateway gagal; pembayaran tetap tersimpan.', ['error' => $e->getMessage(), 'phone' => $payload['phone']]); return; } }
        Log::channel('daily')->info('WA '.$type.' notification (gateway belum dikonfigurasi)', $payload);
    }
}
