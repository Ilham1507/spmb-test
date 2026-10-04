<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Sends outbound messages through Meta's official WhatsApp Business Cloud API. */
class WhatsappCloudApiService
{
    private ?string $resolvedWaslahInstanceKey = null;

    public function send(string $target, string $message): void
    {
        if ($this->usesWaslah()) {
            $response = Http::acceptJson()
                ->withToken($this->waslahToken())
                ->timeout(20)
                ->post('https://waslah.id/api/v1/messages/text', [
                    'instance_key' => $this->waslahInstanceKey(),
                    'to' => $this->normalizeTarget($target),
                    'text' => $message,
                ]);
            if (! $response->successful() || ! $response->json('ok')) {
                throw new RuntimeException((string) ($response->json('message') ?? 'Waslah gagal mengirim pesan.'));
            }
            return;
        }

        $response = $this->request()->post($this->messagesUrl(), [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeTarget($target),
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message,
            ],
        ]);

        if ((int) $response->json('error.code') === 131047) {
            $template = trim((string) config('services.whatsapp.templates.notification'));
            if ($template === '') {
                throw new RuntimeException('Di luar sesi 24 jam: konfigurasi template notifikasi Meta yang disetujui diperlukan.');
            }
            $this->sendTemplate($target, $template, [$message], config('services.whatsapp.template_language', 'id'));
            return;
        }
        $this->throwIfFailed($response->status(), $response->json());
    }

    /** Send a public PDF invoice as an actual WhatsApp document attachment. */
    public function sendDocument(string $target, string $url, string $filename, string $caption = ''): void
    {
        if (! $this->usesWaslah()) {
            $document = ['link' => $url, 'filename' => $filename];
            if ($caption !== '') { $document['caption'] = $caption; }
            $response = $this->request()->post($this->messagesUrl(), [
                'messaging_product' => 'whatsapp', 'to' => $this->normalizeTarget($target),
                'type' => 'document', 'document' => $document,
            ]);
            if ((int) $response->json('error.code') === 131047) {
                $template = trim((string) config('services.whatsapp.templates.invoice'));
                if ($template === '') {
                    throw new RuntimeException('Di luar sesi 24 jam: konfigurasi template invoice Meta yang disetujui diperlukan.');
                }
                $response = $this->request()->post($this->messagesUrl(), [
                    'messaging_product' => 'whatsapp', 'to' => $this->normalizeTarget($target), 'type' => 'template',
                    'template' => ['name' => $template, 'language' => ['code' => config('services.whatsapp.template_language', 'id')],
                        'components' => [
                            ['type' => 'header', 'parameters' => [['type' => 'document', 'document' => ['link' => $url, 'filename' => $filename]]]],
                            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $caption ?: 'Bukti pembayaran SPMB']]],
                        ]],
                ]);
            }
            $this->throwIfFailed($response->status(), $response->json());
            return;
        }

        // Upload the generated invoice first. This avoids a third-party fetch
        // of a short-lived Railway signed URL failing after approval.
        $mediaUrl = $url;
        try {
            $invoice = Http::timeout(30)->get($url);
            if ($invoice->successful()) {
                $upload = Http::acceptJson()
                    ->withToken($this->waslahToken())
                    ->timeout(30)
                    ->attach('file', $invoice->body(), $filename)
                    ->post('https://waslah.id/api/v1/uploads');
                $uploadedUrl = trim((string) $upload->json('data.url'));

                if ($upload->successful() && $upload->json('ok') && $uploadedUrl !== '') {
                    $mediaUrl = $uploadedUrl;
                }
            }
        } catch (\Throwable) {
            // Waslah can still fetch the signed URL directly as a fallback.
        }

        $response = Http::acceptJson()
            ->withToken($this->waslahToken())
            ->timeout(30)
            ->post('https://waslah.id/api/v1/messages/media', [
                'instance_key' => $this->waslahInstanceKey(),
                'to' => $this->normalizeTarget($target),
                'type' => 'document',
                'url' => $mediaUrl,
                'filename' => $filename,
                'mimetype' => 'application/pdf',
                'caption' => $caption,
            ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException((string) ($response->json('message') ?? 'Waslah gagal mengirim PDF invoice.'));
        }
    }

    /**
     * Send an approved Meta template. Use this for a conversation started by
     * the school, such as account activation or password reset.
     *
     * @param array<int, string> $bodyParameters
     */
    public function sendTemplate(string $target, string $template, array $bodyParameters = [], string $language = 'id'): void
    {
        if ($this->usesWaslah()) {
            $message = "Notifikasi SPMB\n\n" . implode("\n", $bodyParameters);
            $this->send($target, $message);
            return;
        }

        if (trim($template) === '') {
            throw new RuntimeException('Nama template WhatsApp belum dikonfigurasi.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeTarget($target),
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $language],
            ],
        ];

        if ($bodyParameters !== []) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(fn (string $value) => ['type' => 'text', 'text' => $value], $bodyParameters),
            ]];
        }

        $response = $this->request()->post($this->messagesUrl(), $payload);
        $this->throwIfFailed($response->status(), $response->json());
    }

    private function request(): PendingRequest
    {
        $token = trim((string) config('services.whatsapp.access_token'));
        if ($token === '') {
            throw new RuntimeException('WhatsApp Business API belum dikonfigurasi. Isi WHATSAPP_ACCESS_TOKEN pada server.');
        }

        return Http::acceptJson()
            ->withToken($token)
            ->timeout(20);
    }

    private function messagesUrl(): string
    {
        $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));
        if ($phoneNumberId === '') {
            throw new RuntimeException('WHATSAPP_PHONE_NUMBER_ID belum diisi.');
        }

        $version = trim((string) config('services.whatsapp.api_version', 'v25.0'));
        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";
    }

    private function usesWaslah(): bool
    {
        $provider = strtolower(trim((string) config('services.whatsapp.provider', 'meta')));
        $waslahToken = trim((string) config('services.whatsapp.waslah_token'));

        if ($provider !== 'waslah') {
            return false;
        }

        if ($waslahToken !== '') {
            return true;
        }

        // A missing Waslah key must not prevent a configured Meta Cloud API
        // sender from delivering every notification.
        return false;
    }

    private function waslahToken(): string
    {
        $token = trim((string) config('services.whatsapp.waslah_token'));

        if ($token === '') {
            throw new RuntimeException('Token Waslah belum dikonfigurasi pada server.');
        }

        return $token;
    }

    private function waslahInstanceKey(): string
    {
        $configuredKey = trim((string) config('services.whatsapp.waslah_instance_key'));

        if ($configuredKey !== '') {
            return $configuredKey;
        }

        if ($this->resolvedWaslahInstanceKey !== null) {
            return $this->resolvedWaslahInstanceKey;
        }

        $response = Http::acceptJson()
            ->withToken($this->waslahToken())
            ->timeout(20)
            ->get('https://waslah.id/api/v1/instances');

        if (! $response->successful()) {
            throw new RuntimeException('Gagal membaca instance WhatsApp yang terhubung.');
        }

        $instances = $response->json('data', []);
        $connectedInstance = collect(is_array($instances) ? $instances : [])
            ->first(fn (array $instance): bool => ($instance['status'] ?? null) === 'connected'
                && filled($instance['key'] ?? null));
        $instanceKey = trim((string) ($connectedInstance['key'] ?? ''));

        if ($instanceKey === '') {
            throw new RuntimeException('Tidak ada instance WhatsApp Waslah yang terhubung.');
        }

        return $this->resolvedWaslahInstanceKey = $instanceKey;
    }

    private function normalizeTarget(string $target): string
    {
        $digits = preg_replace('/\D+/', '', $target) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62') || strlen($digits) < 10 || strlen($digits) > 15) {
            throw new RuntimeException('Nomor tujuan WhatsApp tidak valid.');
        }

        return $digits;
    }

    /** @param array<string, mixed>|null $payload */
    private function throwIfFailed(int $status, ?array $payload): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        $error = $payload['error'] ?? [];
        $detail = $error['error_user_msg'] ?? $error['message'] ?? 'WhatsApp Business API gagal mengirim pesan.';
        throw new RuntimeException((string) $detail);
    }
}
