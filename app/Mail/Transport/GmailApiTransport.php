<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    private ?string $accessToken = null;
    private int $expiresAt = 0;

    public function __construct(private array $credentials)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        // Preserve the complete MIME message, including invoice attachments.
        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');
        $response = Http::withToken($this->token())->timeout(30)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw]);

        if ($response->status() === 401) {
            $this->accessToken = null;
            $response = Http::withToken($this->token())->timeout(30)
                ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => $raw]);
        }

        if (! $response->successful() || ! $response->json('id')) {
            // Do not include response bodies: OAuth responses can contain secrets.
            throw new TransportException('Gmail API rejected email (HTTP '.$response->status().').');
        }
    }

    private function token(): string
    {
        if ($this->accessToken && time() < $this->expiresAt) {
            return $this->accessToken;
        }

        foreach (['client_id', 'client_secret', 'refresh_token'] as $key) {
            if (empty($this->credentials[$key])) {
                throw new TransportException('Gmail API authorization is not configured.');
            }
        }

        $response = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->credentials['client_id'],
            'client_secret' => $this->credentials['client_secret'],
            'refresh_token' => $this->credentials['refresh_token'],
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new TransportException('Gmail API authorization failed (HTTP '.$response->status().').');
        }

        $this->accessToken = $response->json('access_token');
        $this->expiresAt = time() + max(0, (int) $response->json('expires_in', 3600) - 60);

        return $this->accessToken;
    }

    public function __toString(): string
    {
        return 'gmail_api';
    }
}
