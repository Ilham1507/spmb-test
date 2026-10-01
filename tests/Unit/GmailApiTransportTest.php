<?php

namespace Tests\Unit;

use App\Mail\Transport\GmailApiTransport;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class GmailApiTransportTest extends TestCase
{
    public function test_it_sends_complete_mime_with_attachment_and_reuses_access_token(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'gmail.googleapis.com/*' => Http::response(['id' => 'gmail-message-id']),
        ]);
        $transport = $this->transport();
        $email = (new Email())->from('school@example.com')->to('student@example.com')
            ->subject('Verifikasi')->html('<p>Verify</p>')->attach('invoice contents', 'invoice.pdf', 'application/pdf');
        $transport->send($email);
        $transport->send($email);

        Http::assertSentCount(3);
        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'messages/send')) {
                return false;
            }
            $mime = base64_decode(strtr($request['raw'], '-_', '+/'));
            return $request->hasHeader('Authorization', 'Bearer test-token')
                && str_contains($mime, 'invoice.pdf')
                && str_contains($mime, base64_encode('invoice contents'));
        });
    }

    public function test_it_rejects_failed_authorization_without_exposing_secrets(): void
    {
        Http::preventStrayRequests();
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'secret' => 'private'], 400)]);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Gmail API authorization failed (HTTP 400).');
        $this->transport()->send((new Email())->from('school@example.com')->to('student@example.com')->text('Test'));
    }

    private function transport(): GmailApiTransport
    {
        return new GmailApiTransport(['client_id' => 'test', 'client_secret' => 'test', 'refresh_token' => 'test']);
    }
}
