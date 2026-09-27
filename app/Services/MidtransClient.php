<?php

namespace App\Services;

use App\Models\PaymentCheckout;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransClient
{
    public function ready(): bool
    {
        if (app()->environment('production') && ! config('payments.midtrans.production')) return false;
        return (bool) config('payments.midtrans.enabled') && filled(config('payments.midtrans.server_key')) && filled(config('payments.midtrans.merchant_id'));
    }

    private function request()
    {
        if (! $this->ready()) {
            throw new RuntimeException('Pembayaran online belum diaktifkan.');
        }

        $request = Http::withBasicAuth((string) config('payments.midtrans.server_key'), '')
            ->acceptJson()->asJson()->connectTimeout(5)->timeout(15)->withoutRedirecting();
        if ($caBundle = config('payments.midtrans.ca_bundle')) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }
        return $request;
    }

    public function create(PaymentCheckout $checkout): string
    {
        $host = $checkout->production ? 'app.midtrans.com' : 'app.sandbox.midtrans.com';
        // Deliberately no automatic POST retry: an uncertain request keeps its order ID.
        $response = $this->request()->post('https://'.$host.'/snap/v1/transactions', [
            'transaction_details' => ['order_id' => $checkout->order_id, 'gross_amount' => (int) $checkout->amount],
            'enabled_payments' => config('payments.midtrans.enabled_payments'),
            'expiry' => ['start_time' => $checkout->created_at->format('Y-m-d H:i:s O'), 'unit' => 'minutes', 'duration' => (int) config('payments.midtrans.expiry_minutes', 10)],
            'callbacks' => ['finish' => route('peserta.pembayaran')],
        ]);
        if (! $response->successful()) {
            // Never put the provider response or credentials in a user-facing error/log.
            throw new RuntimeException('Penyedia belum mengonfirmasi pembuatan pembayaran.');
        }
        $url = (string) $response->json('redirect_url');
        $this->assertRedirect($url, $checkout->production);

        return $url;
    }

    public function status(PaymentCheckout $checkout): ?array
    {
        if ($checkout->production !== (bool) config('payments.midtrans.production') || $checkout->merchant_id !== config('payments.midtrans.merchant_id')) {
            throw new RuntimeException('Konfigurasi akun pembayaran berbeda dari transaksi.');
        }
        $host = $checkout->production ? 'api.midtrans.com' : 'api.sandbox.midtrans.com';
        $response = $this->request()->get('https://'.$host.'/v2/'.rawurlencode($checkout->order_id).'/status');
        // Snap has no Core status until the payer chooses a payment method.
        if ($response->status() === 404 || (string) $response->json('status_code') === '404') {
            return null;
        }
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Status pembayaran belum bisa diperiksa.');
        }

        return $response->json();
    }

    public function cancel(PaymentCheckout $checkout): void
    {
        if ($checkout->production !== (bool) config('payments.midtrans.production') || $checkout->merchant_id !== config('payments.midtrans.merchant_id')) {
            throw new RuntimeException('Konfigurasi akun pembayaran berbeda dari transaksi.');
        }
        $host = $checkout->production ? 'api.midtrans.com' : 'api.sandbox.midtrans.com';
        $baseUrl = 'https://'.$host.'/v2/'.rawurlencode($checkout->order_id);
        $response = $this->request()->post($baseUrl.'/cancel');
        // Some Snap VA responses acknowledge cancellation with HTTP 2xx but omit
        // transaction_status. A non-2xx response is the only failed cancellation.
        if ($response->successful()) return;

        // VA pending can reject cancel but accept expire. Both actions make the
        // payment code unavailable, so the student can safely create a new order.
        $expireResponse = $this->request()->post($baseUrl.'/expire');
        if (! $expireResponse->successful()) {
            throw new RuntimeException('Penyedia belum mengonfirmasi pembatalan pembayaran.');
        }
    }

    public function validSignature(array $payload): bool
    {
        if (! $this->ready()) return false;
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) return false;
        }
        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('payments.midtrans.server_key'));

        return hash_equals($expected, $payload['signature_key']);
    }

    public function assertRedirect(string $url, bool $production): void
    {
        $parts = parse_url($url);
        $host = $production ? 'app.midtrans.com' : 'app.sandbox.midtrans.com';
        if (! $parts || ($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== $host
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || ! str_starts_with($parts['path'] ?? '', '/snap/')) {
            throw new RuntimeException('Alamat pembayaran tidak valid.');
        }
    }
}
